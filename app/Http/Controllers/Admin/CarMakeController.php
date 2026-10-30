<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CarMake;
use App\Models\CarModel;
use App\Models\Products\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * Admin management of the Cars & Motors reference data: vehicle makes and their
 * models (used by the make/model selects on the product form and the filters).
 *
 * Listings store make/model as plain text in products.attributes, so renaming a
 * make or model here also renames it on existing listings.
 */
class CarMakeController extends Controller
{
    public function index(Request $request)
    {
        $usage = $this->listingUsage();

        $makes = CarMake::query()
            ->withCount('models')
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%' . $request->search . '%'))
            ->orderBy('name')
            ->get()
            ->map(fn (CarMake $make) => [
                'id'             => $make->id,
                'name'           => $make->name,
                'active'         => $make->active,
                'models_count'   => $make->models_count,
                'listings_count' => $usage['makes'][$make->name] ?? 0,
            ]);

        $selected = $request->integer('make')
            ? CarMake::with(['models' => fn ($q) => $q->orderBy('name')])->find($request->integer('make'))
            : null;

        return Inertia::render('Admin/CarMakes/Index', [
            'makes'    => $makes,
            'selected' => $selected ? [
                'id'     => $selected->id,
                'name'   => $selected->name,
                'active' => $selected->active,
                'models' => $selected->models->map(fn (CarModel $m) => [
                    'id'             => $m->id,
                    'name'           => $m->name,
                    'active'         => $m->active,
                    'listings_count' => $usage['models'][$selected->name . '|' . $m->name] ?? 0,
                ]),
            ] : null,
            'filters'  => $request->only(['search']),
        ]);
    }

    // ── Makes ──────────────────────────────────────────────────────────────────

    public function storeMake(Request $request)
    {
        $data = $request->validate([
            'name'   => ['required', 'string', 'max:100', Rule::unique('car_makes', 'name')],
            'models' => ['nullable', 'string', 'max:10000'],
        ]);

        $make = CarMake::create(['name' => trim($data['name']), 'active' => true]);
        $added = $this->addModels($make, $data['models'] ?? '');

        return redirect()->route('admin.car-makes.index', ['make' => $make->id])
            ->with('success', "Make \"{$make->name}\" added" . ($added ? " with {$added} model(s)." : '.'));
    }

    public function updateMake(Request $request, CarMake $carMake)
    {
        $data = $request->validate([
            'name'   => ['sometimes', 'required', 'string', 'max:100', Rule::unique('car_makes', 'name')->ignore($carMake->id)],
            'active' => ['sometimes', 'boolean'],
        ]);

        DB::transaction(function () use ($carMake, $data) {
            $oldName = $carMake->name;

            if (isset($data['name']) && trim($data['name']) !== $oldName) {
                $carMake->name = trim($data['name']);
                $carMake->slug = $this->uniqueMakeSlug($carMake->name, $carMake->id);
                $this->renameOnListings(['make' => $oldName], ['make' => $carMake->name]);
            }
            if (array_key_exists('active', $data)) {
                $carMake->active = $data['active'];
            }

            $carMake->save();
        });

        return redirect()->back()->with('success', 'Make updated.');
    }

    public function destroyMake(CarMake $carMake)
    {
        $name = $carMake->name;
        $carMake->delete(); // models cascade

        return redirect()->route('admin.car-makes.index')
            ->with('success', "Make \"{$name}\" and its models deleted. Existing listings keep their make/model text.");
    }

    // ── Models ─────────────────────────────────────────────────────────────────

    /**
     * Add one or many models (one per line or comma-separated).
     */
    public function storeModels(Request $request, CarMake $carMake)
    {
        $data = $request->validate(['names' => ['required', 'string', 'max:10000']]);

        $added = $this->addModels($carMake, $data['names']);

        return redirect()->back()->with('success', $added ? "{$added} model(s) added." : 'Those models already exist.');
    }

    public function updateModel(Request $request, CarMake $carMake, CarModel $carModel)
    {
        abort_unless($carModel->car_make_id === $carMake->id, 404);

        $data = $request->validate([
            'name'   => ['sometimes', 'required', 'string', 'max:100'],
            'active' => ['sometimes', 'boolean'],
        ]);

        if (isset($data['name']) && trim($data['name']) !== $carModel->name) {
            $name = trim($data['name']);
            $slug = Str::slug($name);

            if ($carMake->models()->where('slug', $slug)->where('id', '!=', $carModel->id)->exists()) {
                return redirect()->back()->withErrors(['name' => "{$carMake->name} already has a model called \"{$name}\"."]);
            }

            DB::transaction(function () use ($carMake, $carModel, $name, $slug) {
                $this->renameOnListings(
                    ['make' => $carMake->name, 'model' => $carModel->name],
                    ['model' => $name],
                );
                $carModel->fill(['name' => $name, 'slug' => $slug]);
                $carModel->save();
            });
        }

        if (array_key_exists('active', $data)) {
            $carModel->update(['active' => $data['active']]);
        }

        return redirect()->back()->with('success', 'Model updated.');
    }

    public function destroyModel(CarMake $carMake, CarModel $carModel)
    {
        abort_unless($carModel->car_make_id === $carMake->id, 404);
        $carModel->delete();

        return redirect()->back()->with('success', "Model \"{$carModel->name}\" deleted.");
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    protected function addModels(CarMake $make, string $raw): int
    {
        $existing = $make->models()->pluck('slug')->all();
        $added = 0;

        collect(preg_split('/[\r\n,]+/', $raw))
            ->map(fn ($n) => trim($n))
            ->filter()
            ->unique(fn ($n) => Str::slug($n))
            ->each(function ($name) use ($make, &$existing, &$added) {
                $slug = Str::slug($name);
                if ($slug === '' || in_array($slug, $existing, true)) {
                    return;
                }
                $make->models()->create(['name' => Str::limit($name, 100, ''), 'slug' => $slug, 'active' => true]);
                $existing[] = $slug;
                $added++;
            });

        return $added;
    }

    protected function uniqueMakeSlug(string $name, int $ignoreId): string
    {
        $base = Str::slug($name) ?: 'make';
        $slug = $base;
        for ($i = 2; CarMake::where('slug', $slug)->where('id', '!=', $ignoreId)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }

    /**
     * Rewrite make/model text on Cars & Motors listings matching $where.
     */
    protected function renameOnListings(array $where, array $set): void
    {
        $query = Product::withoutGlobalScopes()->whereJsonContains('marketplaces', 'cars');
        foreach ($where as $key => $value) {
            $query->where("attributes->{$key}", $value);
        }

        $update = [];
        foreach ($set as $key => $value) {
            $update["attributes->{$key}"] = $value;
        }

        // Query-builder update: skips model events/Scout, as this only relabels reference text.
        $query->toBase()->update($update);
    }

    /**
     * How many Cars & Motors listings use each make and make|model.
     */
    protected function listingUsage(): array
    {
        $attributes = Product::withoutGlobalScopes()
            ->whereJsonContains('marketplaces', 'cars')
            ->pluck('attributes')
            ->map(fn ($a) => is_array($a) ? $a : (json_decode((string) $a, true) ?: []));

        return [
            'makes'  => $attributes->countBy(fn ($a) => $a['make'] ?? '')->all(),
            'models' => $attributes->countBy(fn ($a) => ($a['make'] ?? '') . '|' . ($a['model'] ?? ''))->all(),
        ];
    }
}
