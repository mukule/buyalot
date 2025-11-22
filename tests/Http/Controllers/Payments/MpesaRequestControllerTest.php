<?php

namespace Tests\Http\Controllers\Payments;

use App\Http\DTOs\PaymentResponse;
use App\Models\Orders\Order;
use App\Models\Orders\OrderItem;
use App\Models\ProductVariant;
use App\Services\PaymentService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Mockery;
use Tests\TestCase;


class MpesaRequestControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $paymentService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentService = Mockery::mock(PaymentService::class);
        $this->app->instance(PaymentService::class, $this->paymentService);

        Route::post('/payments/initiate', [\App\Http\Controllers\Payments\MpesaRequestController::class, 'initiate']);
    }

    public function test_initiates_mpesa_payment_successfully()
    {
        $order = Order::factory()->create();
        $variant = ProductVariant::factory()->create(['stock' => 10]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ]);

        $payload = [
            'payable_type' => 'order',
            'payable_id'   => $order->id,
            'amount'       => 100,
            'currency'     => 'KES',
            'provider'     => 'mpesa',
            'method'       => 'mobile_money',
            'phone'        => '254710767015'
        ];

        $paymentLog = (object)[
            'ulid'      => '01HXYZ123',
            'reference' => 'REF123',
            'amount'    => 100,
            'currency'  => 'KES',
            'status'    => (object)['value' => 'pending'],
        ];

        $this->paymentService
            ->shouldReceive('createMpesaRequest')
            ->once()
            ->andReturn($paymentLog);

        $initResponse = new PaymentResponse(
            success: true,
            message: 'STK Push sent',
            data: ['checkout_request_id' => 'ws_CO_12345'],
            errors: null
        );

        $this->paymentService
            ->shouldReceive('initializePayment')
            ->once()
            ->andReturn($initResponse);

        $response = $this->postJson('/payments/initiate', $payload);

        $response->assertCreated()
            ->assertJson([
                'message' => 'STK Push sent',
                'payment' => [
                    'id'        => '01HXYZ123',
                    'reference' => 'REF123',
                    'amount'    => 100,
                    'currency'  => 'KES',
                    'status'    => 'pending',
                ],
                'data' => [
                    'checkout_request_id' => 'ws_CO_12345'
                ]
            ]);
    }

    public function test_blocks_initiation_when_order_is_already_paid()
    {
        $order = Order::factory()->create(['payment_status' => 'paid']);

        $payload = [
            'payable_type' => 'order',
            'payable_id'   => $order->id,
            'amount'       => 100,
            'currency'     => 'KES',
            'provider'     => 'mpesa',
            'method'       => 'mobile_money',
            'phone'        => '254710767015'
        ];

        $response = $this->postJson('/payments/initiate', $payload);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'This order has already been paid.',
            ]);
    }

    public function test_blocks_initiation_due_to_insufficient_stock()
    {
        $order = Order::factory()->create();
        $variant = ProductVariant::factory()->create(['stock' => 1]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_variant_id' => $variant->id,
            'quantity' => 3,
        ]);

        $payload = [
            'payable_type' => 'order',
            'payable_id'   => $order->id,
            'amount'       => 100,
            'currency'     => 'KES',
            'provider'     => 'mpesa',
            'method'       => 'mobile_money',
            'phone'        => '254710767015'
        ];

        $response = $this->postJson('/payments/initiate', $payload);

        $response->assertStatus(409)
            ->assertJson([
                'message' => 'Some items are out of stock or have insufficient quantity.',
            ]);
    }

    public function test_returns_error_when_initialization_fails()
    {
        $order = Order::factory()->create();
        $variant = ProductVariant::factory()->create(['stock' => 5]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $payload = [
            'payable_type' => 'order',
            'payable_id'   => $order->id,
            'amount'       => 100,
            'currency'     => 'KES',
            'provider'     => 'mpesa',
            'method'       => 'mobile_money',
            'phone'        => '254710767015'
        ];

        $paymentLog = (object)[
            'ulid'      => '01HXYZ123',
            'reference' => 'REF123',
            'amount'    => 100,
            'currency'  => 'KES',
            'status'    => (object)['value' => 'pending'],
        ];

        $this->paymentService
            ->shouldReceive('createMpesaRequest')
            ->once()
            ->andReturn($paymentLog);

        $errorResponse = new PaymentResponse(
            success: false,
            message: 'STK Push failed',
            data: null,
            errors: ['MPESA_ERROR']
        );

        $this->paymentService
            ->shouldReceive('initializePayment')
            ->once()
            ->andReturn($errorResponse);

        $response = $this->postJson('/payments/initiate', $payload);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'STK Push failed',
                'errors'  => ['MPESA_ERROR']
            ]);
    }

    public function test_handles_unexpected_exceptions_gracefully()
    {
        $order = Order::factory()->create();

        $payload = [
            'payable_type' => 'order',
            'payable_id'   => $order->id,
            'amount'       => 100,
            'currency'     => 'KES',
            'provider'     => 'mpesa',
            'method'       => 'mobile_money',
            'phone'        => '254710767015'
        ];

        $this->paymentService
            ->shouldReceive('createMpesaRequest')
            ->andThrow(new Exception('Unexpected'));

        $response = $this->postJson('/payments/initiate', $payload);

        $response->assertStatus(500)
            ->assertJson([
                'message' => 'Payment initiation failed',
            ]);
    }
}
