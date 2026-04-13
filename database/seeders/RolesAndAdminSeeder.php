<?php
 namespace Database\Seeders;

 use Illuminate\Database\Seeder;
 use Spatie\Permission\Models\Role;
 use Spatie\Permission\Models\Permission;
 use App\Models\User;
 use Illuminate\Support\Facades\Hash;

 /**
  * Seeds the database with the application's roles, permissions
  * and the initial admin user.
  * 
  * Run with: php artisan db:seed --class=RolesAndAdminSeeder
  * 
  * Roles created:
  *  - admin  : Full platform management access.
  *  - seller : Can list and manage their own products.
  *  - buyer  : Can browse and purchase products.
  * 
  * Permissions created and assigned to admin:
  *  - approve/reject categories
  *  - approve/reject sellers
  *  - manage users
  */
 class RolesAndAdminSeeder extends Seeder 
 {
	/**
	 * Run the database seeds.
	 * 
	 * Uses firstOrCreate throughout to make the seeder safe to run
	 * multiple times without creating duplicate records.
	 */ 
    public function run(): void
    {
       // Reset cached roles and permissions
       app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

       // Create roles
       $admin = Role::firstOrCreate(['name' => 'admin']);
       $seller = Role::firstOrCreate(['name' => 'seller']);
       $buyer = Role::firstOrCreate(['name' => 'buyer']);

       // Create permissions
       $permissions = [
           'approve categories',
           'reject categories',
           'approve sellers',
           'reject sellers',
           'manage users',
       ];

       foreach ($permissions as $permission) {
           Permission::firstOrCreate(['name' => $permission]);
       }

       // Assign all permissions to admin
       $admin->syncPermissions($permissions);

       // Create first admin user
       $adminUser = User::firstOrCreate(
           ['email' => 'admin@ecommerce.local'],
           [
               'name' => 'Admin',
               'password' => Hash::make('admin123456'),
           ]
       );

       $adminUser->assignRole('admin');

       $this->command->info('Roles, permissions and admin user created successfully.');
       $this->command->info('Admin email: admin@ecommerce.local');
       $this->command->info('Admin password: admin123456');
    }
}
