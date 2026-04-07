<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('join_type', 20)->nullable()->after('id');
            $table->string('phone_number', 30)->nullable()->after('password');
            $table->string('birth_date', 8)->nullable()->after('phone_number');
            $table->string('address_postcode', 20)->nullable()->after('birth_date');
            $table->string('address_base')->nullable()->after('address_postcode');
            $table->string('address_detail')->nullable()->after('address_base');
            $table->string('school_name')->nullable()->after('address_detail');
            $table->boolean('is_school_representative')->default(false)->after('school_name');
            $table->boolean('email_marketing_consent')->default(false)->after('is_school_representative');
            $table->timestamp('email_marketing_consent_at')->nullable()->after('email_marketing_consent');
            $table->boolean('kakao_marketing_consent')->default(false)->after('email_marketing_consent_at');
            $table->timestamp('kakao_marketing_consent_at')->nullable()->after('kakao_marketing_consent');
            $table->boolean('sms_marketing_consent')->default(false)->after('kakao_marketing_consent_at');
            $table->timestamp('terms_agreed_at')->nullable()->after('sms_marketing_consent');

            $table->index('join_type');
            $table->index('phone_number');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['join_type']);
            $table->dropIndex(['phone_number']);

            $table->dropColumn([
                'join_type',
                'phone_number',
                'birth_date',
                'address_postcode',
                'address_base',
                'address_detail',
                'school_name',
                'is_school_representative',
                'email_marketing_consent',
                'email_marketing_consent_at',
                'kakao_marketing_consent',
                'kakao_marketing_consent_at',
                'sms_marketing_consent',
                'terms_agreed_at',
            ]);
        });
    }
};
