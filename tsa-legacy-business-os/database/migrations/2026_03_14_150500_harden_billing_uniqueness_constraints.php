<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('payments_gateway_order_id_index');
            $table->dropIndex('payments_gateway_payment_id_index');
            $table->unique('gateway_order_id', 'payments_gateway_order_id_unique');
            $table->unique('gateway_payment_id', 'payments_gateway_payment_id_unique');
        });

        Schema::table('webhook_events', function (Blueprint $table) {
            $table->dropIndex('webhook_events_event_id_index');
            $table->string('payload_hash', 64)->nullable()->after('signature');
            $table->unique('event_id', 'webhook_events_event_id_unique');
            $table->unique('payload_hash', 'webhook_events_payload_hash_unique');
        });
    }

    public function down(): void
    {
        Schema::table('webhook_events', function (Blueprint $table) {
            $table->dropUnique('webhook_events_event_id_unique');
            $table->dropUnique('webhook_events_payload_hash_unique');
            $table->dropColumn('payload_hash');
            $table->index('event_id', 'webhook_events_event_id_index');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique('payments_gateway_order_id_unique');
            $table->dropUnique('payments_gateway_payment_id_unique');
            $table->index('gateway_order_id', 'payments_gateway_order_id_index');
            $table->index('gateway_payment_id', 'payments_gateway_payment_id_index');
        });
    }
};
