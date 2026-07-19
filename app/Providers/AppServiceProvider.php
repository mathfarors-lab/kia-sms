<?php

namespace App\Providers;

use App\Models\Announcement;
use App\Models\Book;
use App\Models\Conversation;
use App\Models\Homework;
use App\Models\HomeworkSubmission;
use App\Models\TransportRoute;
use App\Policies\AnnouncementPolicy;
use App\Policies\BookPolicy;
use App\Policies\ConversationPolicy;
use App\Policies\HomeworkPolicy;
use App\Policies\TransportPolicy;
use App\Support\Permissions;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Some hosts run MySQL/MariaDB without the large-index-prefix default,
        // where a utf8mb4 varchar(255) unique/primary key exceeds the 767-byte
        // limit. Capping the default keeps migrations portable across hosts.
        Schema::defaultStringLength(191);

        // Explicit policy registrations (for models whose name doesn't match Policy class name)
        Gate::policy(Announcement::class,      AnnouncementPolicy::class);
        Gate::policy(Conversation::class,      ConversationPolicy::class);
        Gate::policy(Homework::class,          HomeworkPolicy::class);
        Gate::policy(HomeworkSubmission::class, HomeworkPolicy::class);
        Gate::policy(Book::class,              BookPolicy::class);
        Gate::policy(TransportRoute::class,    TransportPolicy::class);

        // Use our custom KIA pagination view
        Paginator::defaultView('vendor.pagination.kia');

        // Super-admin bypasses all permission gates.
        // For known permission strings: check Spatie and return true/false (fail closed).
        // For unknown abilities (e.g. policy method names like 'view', 'update'): return
        // null so Laravel falls through to the registered Policy for that model.
        Gate::before(function (\App\Models\User $user, string $ability) {
            if ($user->hasRole('admin')) {
                return true;
            }
            if (in_array($ability, Permissions::all(), true)) {
                return $user->hasPermissionTo($ability) ? true : false;
            }
            // Not a registered permission string — let a Policy handle it.
            return null;
        });
    }
}
