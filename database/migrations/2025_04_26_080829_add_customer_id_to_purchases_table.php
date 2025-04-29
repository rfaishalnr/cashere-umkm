<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Purchase;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->string('customer_id')->nullable()->after('id');
        });

        // Populate customer_id for existing records
        $this->populateCustomerIds();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn('customer_id');
        });
    }

    /**
     * Populate customer_id for existing records
     */
    private function populateCustomerIds(): void
    {
        $purchases = Purchase::whereNull('customer_id')->get();
        
        foreach ($purchases as $purchase) {
            if ($purchase->customer_name) {
                $initials = Str::upper(Str::substr($purchase->customer_name, 0, 1) . 
                            (Str::contains($purchase->customer_name, ' ') ? 
                            Str::substr($purchase->customer_name, Str::strpos($purchase->customer_name, ' ') + 1, 1) : 
                            Str::substr($purchase->customer_name, 1, 1)));
                $purchase->customer_id = $initials . Str::random(6);
            } else {
                $purchase->customer_id = 'CUST' . Str::random(6);
            }
            $purchase->save();
        }
    }
};