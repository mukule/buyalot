<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Region;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class RegionController extends Controller
{
    private function parentLevel(string $level): ?string
    {
        return match ($level) {
            'subregion' => 'region',
            'area' => 'subregion',
            'route' => 'area',
            default => null,
        };
    }

    private function pathForLevel(string $level): string
    {
        return match ($level) {
            'subregion' => '/admin/subregions',
            'area' => '/admin/areas',
            'route' => '/admin/routes',
            default => '/admin/regions',
        };
    }

    private function redirectToLevel(string $level)
    {
        return redirect($this->pathForLevel($level));
    }

    private function detectLevel(Request $request): string
    {
        $path = $request->path();
        if (str_contains($path, 'subregions')) return 'subregion';
        if (str_contains($path, 'areas')) return 'area';
        if (str_contains($path, 'routes')) return 'route';
        return 'region';
    }




public function index(Request $request)
{
    Log::info('Admin/RegionController@index called', [
        'user_id' => auth()->id(),
        'route' => request()->fullUrl(),
    ]);

    $level = $request->get('level') ?? $this->detectLevel($request);

    $regions = Region::query()
        ->level($level)
        ->orderBy('created_at', 'desc')
        ->paginate(15)
        ->withQueryString();

    return Inertia::render('Admin/Regions/Index', [
        'regions' => $regions,
        'level' => $level,
        'title' => ucfirst($level === 'region' ? 'Regions' : ($level === 'subregion' ? 'Subregions' : ($level === 'area' ? 'Areas' : 'Routes'))),
        'basePath' => $this->pathForLevel($level),
    ]);
}


    public function create(Request $request)
    {
        $level = $request->get('level') ?? $this->detectLevel($request);
        $parentLevel = $this->parentLevel($level);

        $parents = $parentLevel ? Region::query()->level($parentLevel)->orderBy('name')->get(['id','name']) : [];

        return Inertia::render('Admin/Regions/Create', [
            'level' => $level,
            'parents' => $parents,
            'title' => 'Create ' . ucfirst($level === 'region' ? 'Region' : ($level === 'subregion' ? 'Subregion' : ($level === 'area' ? 'Area' : 'Route'))),
            'basePath' => $this->pathForLevel($level),
        ]);
    }

    /**
     * Store a newly created region.
     */
    public function store(Request $request)
    {
        $level = $request->get('level') ?? $this->detectLevel($request);
        $rules = [
            'name' => ['required', 'string', 'max:255', 'unique:regions,name'],
        ];
        if ($level !== 'region') {
            $rules['parent_id'] = ['required', 'exists:regions,id'];
        }
        $validated = $request->validate($rules);

        $region = Region::create([
            'name' => $validated['name'],
            'code' => strtoupper(substr(Str::slug($validated['name'], ''), 0, 5)),
            'level' => $level,
            'parent_id' => $validated['parent_id'] ?? null,
        ]);

        return $this->redirectToLevel($level)
            ->with('success', ucfirst($level) . ' created successfully.');
    }

    /**
     * Show the form for editing a region.
     */
    public function edit(Request $request, Region $region)
    {
        $level = $request->get('level') ?? ($region->level ?? $this->detectLevel($request));
        $parentLevel = $this->parentLevel($level);
        $parents = $parentLevel ? Region::query()->level($parentLevel)->orderBy('name')->get(['id','name']) : [];
        return Inertia::render('Admin/Regions/Edit', [
            'region' => $region,
            'level' => $level,
            'parents' => $parents,
            'title' => 'Edit ' . ucfirst($level === 'region' ? 'Region' : ($level === 'subregion' ? 'Subregion' : ($level === 'area' ? 'Area' : 'Route'))),
            'basePath' => $this->pathForLevel($level),
        ]);
    }

    /**
     * Update an existing region.
     */
    public function update(Request $request, Region $region)
    {
        $level = $request->get('level') ?? ($region->level ?? $this->detectLevel($request));
        $rules = [
            'name' => ['required', 'string', 'max:255', 'unique:regions,name,' . $region->id],
        ];
        if ($level !== 'region') {
            $rules['parent_id'] = ['required', 'exists:regions,id'];
        }
        $validated = $request->validate($rules);

        $region->update([
            'name' => $validated['name'],
            'code' => strtoupper(substr(Str::slug($validated['name'], ''), 0, 5)),
            'level' => $level,
            'parent_id' => $validated['parent_id'] ?? null,
        ]);

        return $this->redirectToLevel($level)
            ->with('success', ucfirst($level) . ' updated successfully.');
    }

    /**
     * Show a single region.
     */
    public function show(Request $request, Region $region)
    {
        $level = $request->get('level') ?? ($region->level ?? $this->detectLevel($request));
        return Inertia::render('Admin/Regions/Show', [
            'region' => $region,
            'level' => $level,
        ]);
    }

    /**
     * Delete a region.
     */
    public function destroy(Request $request, Region $region)
    {
        $level = $request->get('level') ?? ($region->level ?? $this->detectLevel($request));
        $region->delete();

        return $this->redirectToLevel($level)
            ->with('success', ucfirst($level) . ' deleted successfully.');
    }
}
