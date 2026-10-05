<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Attachment;
use App\Models\Comment;
use App\Models\Favorite;
use App\Models\Label;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\Subtask;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use App\Models\WorkspaceMember;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed demo data for WorkFlow Pro.
     */
    public function run(): void
    {
        $this->clearExistingData();

        $users = $this->seedUsers();
        $workspace = $this->seedWorkspace($users);
        $labels = $this->seedLabels($workspace);
        $projects = $this->seedProjects($workspace, $users);
        $milestones = $this->seedMilestones($projects['ecommerce']);
        $this->seedTasks($projects, $users, $labels, $milestones);
        $this->seedFavorites($users['hong'], $projects['ecommerce']);
        $this->seedActivities($workspace, $users);

        $this->command->info('✅ Demo data seeded! Đăng nhập: hong@example.com / password123');
    }

    /**
     * Wipe previous demo data so the seeder can run multiple times
     * (php artisan db:seed) without unique-constraint errors.
     */
    private function clearExistingData(): void
    {
        Schema::disableForeignKeyConstraints();

        foreach ([
            'notifications',
            'user_settings',
            'recently_vieweds',
            'favorites',
            'activities',
            'attachments',
            'comments',
            'task_labels',
            'labels',
            'subtasks',
            'tasks',
            'milestones',
            'project_members',
            'projects',
            'workspace_invitations',
            'workspace_members',
            'workspaces',
            'users',
        ] as $table) {
            DB::table($table)->delete();
        }

        Schema::enableForeignKeyConstraints();

        // Remove attachment files from previous seeds.
        Storage::disk('public')->deleteDirectory('attachments');
    }

    /* ------------------------------------------------------------------
     |  Users
     * ------------------------------------------------------------------ */

    private function seedUsers(): array
    {
        $data = [
            'hong' => ['Nguyễn Hoàng', 'hong@example.com'],
            'minh' => ['Trần Minh', 'minh@example.com'],
            'an' => ['Lê An', 'an@example.com'],
            'long' => ['Phạm Long', 'long@example.com'],
            'mai' => ['Vũ Mai', 'mai@example.com'],
        ];

        $users = [];

        foreach ($data as $key => [$name, $email]) {
            $users[$key] = User::create([
                'name' => $name,
                'email' => $email,
                'password' => 'password123',
                'email_verified_at' => now()->subDays(30),
                'created_at' => now()->subDays(30),
            ]);
        }

        return $users;
    }

    /* ------------------------------------------------------------------
     |  Workspace
     * ------------------------------------------------------------------ */

    private function seedWorkspace(array $users): Workspace
    {
        $workspace = Workspace::create([
            'name' => 'Hong Development',
            'description' => 'Software development team — xây dựng sản phẩm web & mobile.',
            'created_by' => $users['hong']->id,
            'created_at' => now()->subDays(25),
        ]);

        $roles = [
            'hong' => 'owner',
            'minh' => 'manager',
            'an' => 'member',
            'long' => 'member',
            'mai' => 'member',
        ];

        foreach ($roles as $key => $role) {
            WorkspaceMember::create([
                'workspace_id' => $workspace->id,
                'user_id' => $users[$key]->id,
                'role' => $role,
                'created_at' => now()->subDays(25 - array_search($key, array_keys($roles))),
            ]);
        }

        // A pending invitation for demo purposes.
        WorkspaceInvitation::create([
            'workspace_id' => $workspace->id,
            'invited_by' => $users['hong']->id,
            'email' => 'newmember@example.com',
            'role' => 'member',
            'token' => 'demo-invite-token-1234567890',
            'expires_at' => now()->addDays(7),
        ]);

        return $workspace;
    }

    /* ------------------------------------------------------------------
     |  Labels
     * ------------------------------------------------------------------ */

    private function seedLabels(Workspace $workspace): array
    {
        $labels = [];

        foreach ([
            'Backend' => '#ef4444',
            'Frontend' => '#3b82f6',
            'Bug' => '#f59e0b',
            'Design' => '#a855f7',
            'Documentation' => '#10b981',
        ] as $name => $color) {
            $labels[$name] = $workspace->labels()->create([
                'name' => $name,
                'color' => $color,
            ]);
        }

        return $labels;
    }

    /* ------------------------------------------------------------------
     |  Projects
     * ------------------------------------------------------------------ */

    private function seedProjects(Workspace $workspace, array $users): array
    {
        $ecommerce = $workspace->projects()->create([
            'name' => 'E-commerce Website',
            'description' => 'Website bán hàng trực tuyến: giỏ hàng, thanh toán, quản lý sản phẩm.',
            'start_date' => now()->subDays(20),
            'due_date' => now()->addDays(40),
            'status' => 'active',
            'priority' => 'high',
            'created_by' => $users['hong']->id,
            'created_at' => now()->subDays(20),
        ]);

        $mobile = $workspace->projects()->create([
            'name' => 'Mobile Application',
            'description' => 'Ứng dụng mobile đồng bộ với website (Flutter).',
            'start_date' => now()->subDays(5),
            'due_date' => now()->addDays(75),
            'status' => 'planning',
            'priority' => 'medium',
            'created_by' => $users['minh']->id,
            'created_at' => now()->subDays(5),
        ]);

        $company = $workspace->projects()->create([
            'name' => 'Company Website',
            'description' => 'Website giới thiệu công ty, blog và form liên hệ.',
            'start_date' => now()->subDays(40),
            'due_date' => now()->subDays(5),
            'status' => 'on_hold',
            'priority' => 'low',
            'created_by' => $users['minh']->id,
            'created_at' => now()->subDays(40),
        ]);

        // Archived example project.
        $old = $workspace->projects()->create([
            'name' => 'Landing Page 2025',
            'description' => 'Landing page sự kiện cuối năm (đã hoàn thành, lưu trữ).',
            'start_date' => now()->subDays(120),
            'due_date' => now()->subDays(60),
            'status' => 'archived',
            'priority' => 'medium',
            'created_by' => $users['hong']->id,
            'created_at' => now()->subDays(120),
        ]);

        $ecommerce->members()->attach([$users['hong']->id, $users['minh']->id, $users['an']->id, $users['long']->id]);
        $mobile->members()->attach([$users['hong']->id, $users['minh']->id]);
        $company->members()->attach([$users['minh']->id, $users['an']->id]);
        $old->members()->attach([$users['hong']->id]);

        return ['ecommerce' => $ecommerce, 'mobile' => $mobile, 'company' => $company];
    }

    /* ------------------------------------------------------------------
     |  Milestones
     * ------------------------------------------------------------------ */

    private function seedMilestones(Project $project): array
    {
        return [
            'auth' => $project->milestones()->create([
                'name' => 'Authentication',
                'description' => 'Đăng nhập, đăng ký, phân quyền.',
                'due_date' => now()->addDays(10),
            ]),
            'product' => $project->milestones()->create([
                'name' => 'Product Management',
                'description' => 'Quản lý danh mục và sản phẩm.',
                'due_date' => now()->addDays(20),
            ]),
            'payment' => $project->milestones()->create([
                'name' => 'Payment',
                'description' => 'Tích hợp cổng thanh toán.',
                'due_date' => now()->addDays(35),
            ]),
        ];
    }

    /* ------------------------------------------------------------------
     |  Tasks
     * ------------------------------------------------------------------ */

    private function seedTasks(array $projects, array $users, array $labels, array $milestones): void
    {
        $ecommerce = $projects['ecommerce'];

        $tasks = [
            // [title, description, status, priority, assignee, dueOffsetDays, milestone, labels[], project]
            ['Design Homepage', 'Thiết kế trang chủ theo brand mới.', 'done', 'medium', 'an', -6, null, ['Design', 'Frontend'], 'ecommerce'],
            ['Header component', 'Header responsive với menu.', 'done', 'low', 'minh', -5, null, ['Frontend'], 'ecommerce'],
            ['Create login form', 'Form đăng nhập + validate client-side.', 'done', 'high', 'an', -4, 'auth', ['Frontend'], 'ecommerce'],
            ['Implement Login API', 'API xác thực với JWT + rate limit.', 'done', 'high', 'long', -3, 'auth', ['Backend'], 'ecommerce'],
            ['Database design', 'Sơ đồ CSDL: users, products, orders.', 'done', 'medium', 'minh', -3, 'product', ['Backend'], 'ecommerce'],
            ['Product UI', 'Trang danh sách + chi tiết sản phẩm.', 'review', 'medium', 'an', 0, 'product', ['Frontend', 'Design'], 'ecommerce'],
            ['Create validation', 'Validate dữ liệu đăng ký/đăng nhập.', 'in_progress', 'high', 'hong', 2, 'auth', ['Backend'], 'ecommerce'],
            ['Product API', 'CRUD sản phẩm + tìm kiếm + phân trang.', 'in_progress', 'medium', 'long', 4, 'product', ['Backend'], 'ecommerce'],
            ['Fix payment bug', 'Lỗi 500 khi thanh toán với VNPay.', 'in_progress', 'urgent', 'an', -2, 'payment', ['Bug', 'Backend'], 'ecommerce'],
            ['Payment API', 'Tích hợp VNPay + Momo, lưu transaction.', 'todo', 'urgent', 'long', 1, 'payment', ['Backend'], 'ecommerce'],
            ['Checkout UI', 'Trang thanh toán nhiều bước.', 'todo', 'high', 'hong', 7, 'payment', ['Frontend'], 'ecommerce'],
            ['Handle authentication', 'Refresh token + logout mọi thiết bị.', 'todo', 'medium', 'long', 6, 'auth', ['Backend'], 'ecommerce'],
            ['Test login', 'Viết feature test cho luồng đăng nhập.', 'todo', 'medium', 'hong', 9, 'auth', ['Backend'], 'ecommerce'],
            ['User Profile page', 'Trang hồ sơ cá nhân + đổi avatar.', 'todo', 'medium', 'mai', 12, null, ['Frontend'], 'ecommerce'],
            ['Update documentation', 'Cập nhật API docs cho team.', 'todo', 'low', 'minh', 15, null, ['Documentation'], 'ecommerce'],

            // Mobile Application
            ['Setup Flutter project', 'Khởi tạo dự án, CI/CD cơ bản.', 'done', 'medium', 'minh', -1, null, [], 'mobile'],
            ['App architecture', 'Chọn state management + folder structure.', 'in_progress', 'high', 'minh', 5, null, ['Backend'], 'mobile'],
            ['Login screen', 'Màn hình đăng nhập giống web.', 'todo', 'medium', 'minh', 12, null, ['Frontend'], 'mobile'],
            ['Sync API với backend', 'Dùng lại các endpoint của E-commerce.', 'todo', 'low', null, 20, null, [], 'mobile'],

            // Company Website
            ['Trang giới thiệu', 'Giới thiệu công ty + team.', 'done', 'low', 'an', -8, null, ['Frontend'], 'company'],
            ['Blog module', 'Danh sách bài viết + chi tiết.', 'todo', 'medium', 'an', 10, null, [], 'company'],
            ['Form liên hệ', 'Form gửi email về CRM.', 'todo', 'low', null, 14, null, [], 'company'],
        ];

        $positionCounters = [];

        foreach ($tasks as [$title, $description, $status, $priority, $assignee, $dueOffset, $milestone, $taskLabels, $projectKey]) {
            $project = $projects[$projectKey];
            $statusKey = $status;
            $positionCounters[$projectKey.'_'.$statusKey] = ($positionCounters[$projectKey.'_'.$statusKey] ?? 0) + 1;

            $task = $project->tasks()->create([
                'title' => $title,
                'description' => $description,
                'status' => $status,
                'priority' => $priority,
                'assignee_id' => $assignee ? $users[$assignee]->id : null,
                'created_by' => $users['minh']->id,
                'milestone_id' => $milestone ? $milestones[$milestone]->id : null,
                'due_date' => $dueOffset !== null ? now()->addDays($dueOffset) : null,
                'position' => $positionCounters[$projectKey.'_'.$statusKey],
                'created_at' => now()->subDays(rand(3, 15)),
            ]);

            if ($taskLabels) {
                $task->labels()->attach(array_map(fn ($name) => $labels[$name]->id, $taskLabels));
            }

            // Historical completion time for done tasks.
            if ($status === 'done') {
                $task->forceFill([
                    'completed_at' => now()->addDays($dueOffset)->endOfDay(),
                ])->saveQuietly();
            }
        }

        // Subtasks for "Implement Login API" (task is done → most subtasks done).
        $loginApi = $ecommerce->tasks()->where('title', 'Implement Login API')->first();
        foreach ([
            ['Create endpoint', true],
            ['Password hashing', true],
            ['JWT token', true],
            ['Rate limiting', false],
            ['Write tests', false],
        ] as $i => [$title, $done]) {
            Subtask::create([
                'task_id' => $loginApi->id,
                'title' => $title,
                'is_completed' => $done,
                'position' => $i + 1,
            ]);
        }

        // Subtasks for "Payment API".
        $paymentApi = $ecommerce->tasks()->where('title', 'Payment API')->first();
        foreach (['Tạo order endpoint', 'Tích hợp VNPay sandbox', 'Tích hợp Momo sandbox'] as $i => $title) {
            Subtask::create([
                'task_id' => $paymentApi->id,
                'title' => $title,
                'is_completed' => false,
                'position' => $i + 1,
            ]);
        }

        // Comments matching the spec example.
        $paymentBug = $ecommerce->tasks()->where('title', 'Fix payment bug')->first();

        $c1 = Comment::create([
            'task_id' => $paymentBug->id,
            'user_id' => $users['minh']->id,
            'body' => 'Lỗi 500 xảy ra khi khách thanh toán bằng VNPay. @An bạn kiểm tra lại phần callback giúp mình.',
            'created_at' => now()->subHours(26),
        ]);

        Comment::create([
            'task_id' => $paymentBug->id,
            'user_id' => $users['an']->id,
            'parent_id' => $c1->id,
            'body' => 'Đã tìm ra nguyên nhân: signature chưa được verify. Mình đang fix, xong sẽ update.',
            'created_at' => now()->subHours(20),
        ]);

        Comment::create([
            'task_id' => $paymentBug->id,
            'user_id' => $users['minh']->id,
            'body' => 'OK, fix xong nhớ thêm log để dễ debug sau này nhé.',
            'created_at' => now()->subHours(5),
        ]);

        // Attachments (real dummy files on disk).
        $loginApi = $ecommerce->tasks()->where('title', 'Implement Login API')->first();

        $files = [
            'api-document.txt' => "API Document — Login\n\nPOST /api/login\nBody: email, password\nResponse: { token, user }\n\nRate limit: 5 requests/minute.",
            'requirements.txt' => "Yêu cầu chức năng Authentication:\n\n1. Đăng nhập bằng email/password\n2. Đăng ký + xác minh email\n3. Quên mật khẩu\n4. Đổi mật khẩu\n5. Rate limit chống brute force",
        ];

        foreach ($files as $fileName => $content) {
            $path = 'attachments/project-'.$ecommerce->id.'/'.uniqid().'.txt';
            Storage::disk('public')->put($path, $content);

            Attachment::create([
                'task_id' => $loginApi->id,
                'user_id' => $users['long']->id,
                'file_path' => $path,
                'file_name' => $fileName,
                'file_size' => strlen($content),
                'mime_type' => 'text/plain',
            ]);
        }
    }

    /* ------------------------------------------------------------------
     |  Favorites & Activities
     * ------------------------------------------------------------------ */

    private function seedFavorites(User $user, Project $project): void
    {
        Favorite::create([
            'user_id' => $user->id,
            'project_id' => $project->id,
        ]);
    }

    private function seedActivities(Workspace $workspace, array $users): void
    {
        $project = $workspace->projects()->where('name', 'E-commerce Website')->first();
        $log = [
            ['workspace_created', $users['hong'], -25, []],
            ['member_joined', $users['minh'], -24, ['member' => 'Trần Minh', 'via' => 'invitation']],
            ['member_joined', $users['an'], -23, ['member' => 'Lê An', 'via' => 'invitation']],
            ['member_joined', $users['long'], -22, ['member' => 'Phạm Long', 'via' => 'invitation']],
            ['project_created', $users['hong'], -20, ['project' => 'E-commerce Website']],
            ['milestone_created', $users['hong'], -20, ['milestone' => 'Authentication']],
            ['task_created', $users['minh'], -15, ['task' => 'Design Homepage']],
            ['task_status_changed', $users['an'], -6, ['task' => 'Design Homepage', 'old' => 'in_progress', 'new' => 'done']],
            ['task_created', $users['minh'], -10, ['task' => 'Implement Login API']],
            ['task_assigned', $users['minh'], -10, ['task' => 'Implement Login API', 'assignee' => 'Phạm Long']],
            ['commented', $users['minh'], -1, ['task' => 'Fix payment bug']],
            ['task_status_changed', $users['long'], -3, ['task' => 'Implement Login API', 'old' => 'in_progress', 'new' => 'done']],
            ['task_status_changed', $users['an'], -1, ['task' => 'Product UI', 'old' => 'in_progress', 'new' => 'review']],
            ['uploaded', $users['long'], -2, ['task' => 'Implement Login API', 'file' => 'api-document.txt']],
            ['commented', $users['an'], -1, ['task' => 'Fix payment bug']],
        ];

        foreach ($log as [$type, $user, $daysAgo, $data]) {
            Activity::create([
                'workspace_id' => $workspace->id,
                'project_id' => in_array($type, ['workspace_created', 'member_joined']) ? null : $project->id,
                'user_id' => $user->id,
                'type' => $type,
                'data' => $data,
                'created_at' => now()->subDays(abs($daysAgo)),
            ]);
        }
    }
}
