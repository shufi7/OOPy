-- MySQL preview from php artisan migrate --pretend on 20 November 2026.
-- REVIEW PREVIEW: applied via Laravel to local development after user approval, 20 November 2026.
-- Do not rerun this SQL manually on an already migrated database.
-- Existing orders default to simulation; existing certificates are not updated.
alter table `certificate_orders` add `provider` varchar(24) not null default 'simulation';
alter table `certificate_orders` add `eligible_completed_at` datetime null;
alter table `certificate_orders` add `paid_at` datetime null;
alter table `certificate_orders` add `verified_at` datetime null;
alter table `certificate_orders` add `paid_provider_order` varchar(50) null;
create table `certificate_payment_attempts` (
    `id` bigint unsigned not null auto_increment primary key,
    `public_id` char(36) not null,
    `certificate_order_id` bigint unsigned not null,
    `order_number` varchar(50) not null,
    `provider_transaction_id` varchar(100) null,
    `merchant_id` varchar(100) null,
    `status` varchar(32) not null default 'creating',
    `payment_method` varchar(32) null,
    `fraud_status` varchar(24) null,
    `snap_token` text null,
    `redirect_url` text null,
    `request_started_at` datetime null,
    `expires_at` datetime not null,
    `verified_at` datetime null,
    `created_at` timestamp null,
    `updated_at` timestamp null
) default character set utf8mb4 collate 'utf8mb4_unicode_ci';
alter table `certificate_payment_attempts` add constraint `certificate_payment_attempts_certificate_order_id_foreign`
    foreign key (`certificate_order_id`) references `certificate_orders` (`id`) on delete cascade;
alter table `certificate_payment_attempts` add index `certificate_attempt_order_created_index` (`certificate_order_id`, `created_at`);
alter table `certificate_payment_attempts` add unique `certificate_payment_attempts_public_id_unique` (`public_id`);
alter table `certificate_payment_attempts` add unique `certificate_payment_attempts_order_number_unique` (`order_number`);
alter table `certificate_payment_attempts` add index `certificate_payment_attempts_provider_transaction_id_index` (`provider_transaction_id`);
