<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use App\Models\User;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // - PERMISSIONS
        $permissions = [
            // Users
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
            'users.assign-roles',

            // Profiles
            'profiles.view',
            'profiles.edit-own',
            'profiles.edit-any',

            // Employment
            'employment.view',
            'employment.hire', // platform-level (owner/admin)
            'employment.hire-for-store', // store-level (seller)
            'employment.fire',

            // Teams
            'teams.view',
            'teams.create',
            'teams.edit-own', // team owner can edit their team
            'teams.edit-any', 
            'teams.delete-own',
            'teams.delete-any',
            'teams.manage-members', // add/remove members from own team
            'teams.manage-any-members', // add/remove from any team
            
            // Staff Spotlights
            'spotlights.view',
            'spotlights.manage', // owner/admin only
            
            // Sellers
            'sellers.view',
            'sellers.apply', // apply to become a seller 
            'sellers.approve', // approve/reject applications
            'sellers.edit-own',
            'sellers.edit-any',
            'sellers.suspend',

            // Products
            'products.view',
            'products.create',
            'products.edit-own',
            'products.edit-any',
            'products.delete-own',
            'products.delete-any',

            // Orders
            'orders.view-own',
            'orders.view-any',
            'orders.update-status',
            'orders.cancel-own', 
            'orders.cancel-any',

            // Cart
            'cart.manage',

            // Wishlist
            'wishlist.manage',
 
            // Blog
            'blog.view',
            'blog.create',
            'blog.edit-own',
            'blog.edit-any',
            'blog.delete-own',
            'blog.delete-any',
            'blog.publish',

            // FAQ
            'faq.view',
            'faq.manage',

            // Contacts
            'contacts.submit',
            'contacts.view-own',
            'contacts.view-any',
            'contacts.reply',
            'contacts.delete',

            // Settings
            'settings.view',
            'settings.manage',
       ];
 
       foreach ($permissions as $perm) {
           Permission::firstOrCreate([
               'name' => $perm,
               'guard_name' => 'web',
           ]);
       }

       // - ROLES

       // CUSTOMER - registered buyer
       $customer = Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
       $customer->syncPermissions([
           'profiles.view', 'profiles.edit-own',
           'products.view',
           'orders.view-own', 'orders.cancel-own',
           'cart.manage',
           'wishlist.manage',
           'blog.view',
           'faq.view',
           'contacts.submit', 'contacts.view-own',
           'sellers.apply',
           'teams.view',
           'spotlights.view',
       ]);

       // EMPLOYEE - hired by owner or seller
       $employee = Role::firstOrCreate(['name' => 'employee', 'guard_name' => 'web']);
       $employee->syncPermissions([
           'profiles.view', 'profiles.edit-own',
           'products.view', 'products.create', 'products.edit-own',
           'orders.view-any', 'orders.update-status',
           'cart.manage',
           'blog.view',
           'faq.view',
           'contacts.submit', 'contacts.view-own',
           'teams.view',
           'spotlights.view',
       ]);

       // EDITOR - content manager 
       $editor = Role::firstOrCreate(['name' => 'editor', 'guard_name' => 'web']);
       $editor->syncPermissions([
           'profiles.view', 'profiles.edit-own',
           'products.view',
           'orders.view-own',
           'cart.manage',
           'blog.view', 'blog.create', 'blog.edit-own',
           'blog.delete-own', 'blog.publish',
           'faq.view', 'faq.manage',
           'contacts.submit', 'contacts.view-any', 'contacts.reply',
           'teams.view', 'teams.create', 'teams.edit-own',
           'teams.delete-own', 'teams.manage-members',
           'spotlights.view',
       ]);

       // SELLER - owns a store
       $seller = Role::firstOrCreate(['name' => 'seller', 'guard_name' => 'web']);
       $seller->syncPermissions([
           'profiles.view', 'profiles.edit-own',
           'products.view', 'products.create',
           'products.edit-own', 'products.delete-own',
           'orders.view-own', 'orders.view-any', 'orders.update-status', 'orders.cancel-own',
           'cart.manage',
           'wishlist.manage',
           'blog.view',
           'faq.view',
           'contacts.submit', 'contacts.view-own',
           'sellers.edit-own',
           'employment.view', 'employment.hire-for-store', 'employment.fire',
           'teams.view', 'teams.create', 'teams.edit-own',
           'teams.delete-own', 'teams.manage-members',
           'spotlights.view',
       ]);

       // ADMIN - platform management
       $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
       $admin->syncPermissions([
           'users.view', 'users.create', 'users.edit', 'users.assign-roles',
           'profiles.view', 'profiles.edit-own', 'profiles.edit-any',
           'employment.view', 'employment.hire', 'employment.fire',
           'teams.view', 'teams.create', 'teams.edit-own', 'teams.edit-any',
           'teams.delete-own', 'teams.delete-any',
           'teams.manage-members', 'teams.manage-any-members',
           'spotlights.view', 'spotlights.manage',
           'sellers.view', 'sellers.approve', 'sellers.edit-any', 'sellers.suspend',
           'products.view', 'products.edit-any', 'products.delete-any',
           'orders.view-any', 'orders.update-status', 'orders.cancel-any',
           'cart.manage', 'wishlist.manage',
           'blog.view', 'blog.create', 'blog.edit-own', 'blog.edit-any',
           'faq.view', 'faq.manage',
           'contacts.submit', 'contacts.view-any', 'contacts.reply', 'contacts.delete',
           'settings.view',
       ]);

       // OWNER - full platform control
       $owner = Role::firstOrCreate(['name' => 'owner', 'guard_name' => 'web']);
       $owner->syncPermissions(Permission::all());

       // - ASSIGN OWNER TO ADMIN USER

       $adminUser = User::where('email', 'admin@ecommerce.local')->first();
       if ($adminUser) {
           if (!$adminUser->hasRole('owner')) {
               $adminUser->assignRole('owner');
           }
           echo " owner role -> admin@ecommerce.local \n";
       }

       echo " ". Permission::count() ." permissions seeded \n";
       echo " ". Role::count() ." roles seeded \n";
   }
}
