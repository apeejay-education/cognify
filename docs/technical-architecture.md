# Cognify Technical Architecture v3.0

**Version:** 3.0  
**Date:** October 27, 2025  
**Status:** Draft  
**Architecture Type:** PHP/Laravel Multi-Tenant SaaS Platform  
**Based on:** PRD v3.0 & Feature Brainstorming Results

---

## 1. Architecture Overview

### 1.1 System Architecture Pattern
**Multi-Tenant SaaS Architecture** with **Domain-Driven Design (DDD)** principles using PHP/Laravel framework.

```
┌─────────────────────────────────────────────────────────────┐
│                    COGNIFY PLATFORM                         │
├─────────────────────────────────────────────────────────────┤
│  SuperAdmin Portal  │         Client Portals               │
│  (cognify.com)      │    (client1.cognify.com)            │
│                     │    (client2.cognify.com)            │
├─────────────────────────────────────────────────────────────┤
│                Laravel Application Layer                     │
├─────────────────────────────────────────────────────────────┤
│     Multi-Tenant Database     │    Shared Services         │
│                               │    (AI, Storage, etc.)     │
└─────────────────────────────────────────────────────────────┘
```

### 1.2 Core Architecture Principles
- **Multi-Tenancy:** Single application serving multiple institutes with data isolation
- **Modular Design:** Laravel packages for each major module
- **API-First:** RESTful APIs with Laravel Sanctum authentication
- **Real-time:** Laravel Broadcasting with WebSockets
- **Scalable:** Horizontal scaling capability with load balancing
- **Secure:** Role-based access control with complete tenant isolation

---

## 2. Technology Stack

### 2.1 Core Framework & Language
```php
// Core Stack
- PHP 8.2+
- Laravel 10+ Framework
- Composer for dependency management
```

### 2.2 Database & Caching
```yaml
Primary Database: MySQL 8.0+
Caching Layer: Redis 7.0+
Session Storage: Redis
Queue Backend: Redis
Search Engine: Laravel Scout + Meilisearch
```

### 2.3 Frontend Technologies
```yaml
Web Frontend: Laravel Blade + Alpine.js + Tailwind CSS
Mobile: Progressive Web App (PWA) + Laravel APIs
Real-time: Laravel Broadcasting + Pusher/WebSockets
Asset Compilation: Laravel Vite
```

### 2.4 External Services & APIs
```yaml
AI Services:
  - OpenAI API (GPT-4 for text generation, summarization)
  - Google Speech-to-Text API (voice assessments)
  - Google Cloud Vision API (future video analysis)

Communication:
  - Twilio (SMS, Voice calls)
  - SendGrid/Amazon SES (Email)
  - Firebase (Push notifications)

Payments:
  - Razorpay (Primary - India)
  - Stripe (International)

Video Conferencing:
  - Zoom API (live classes)

Storage:
  - Amazon S3 / DigitalOcean Spaces
  - Local storage for development
```

---

## 3. Multi-Tenant Architecture

### 3.1 Tenancy Strategy: **Database Per Tenant with Shared Services**

```php
// Tenant Resolution Middleware
class TenantResolver
{
    public function handle($request, Closure $next)
    {
        $subdomain = $this->extractSubdomain($request->getHost());
        
        if ($subdomain === 'app' || $subdomain === 'superadmin') {
            // SuperAdmin portal
            config(['database.default' => 'superadmin']);
        } else {
            // Client portal
            $tenant = Tenant::findBySubdomain($subdomain);
            config(['database.default' => $tenant->database_name]);
            app()->instance('tenant', $tenant);
        }
        
        return $next($request);
    }
}
```

### 3.2 Database Architecture

#### SuperAdmin Database (`cognify_superadmin`)
```sql
-- Core tenant management
tenants (id, name, subdomain, database_name, status, created_at)
subscription_plans (id, name, features, price, user_limit)
subscriptions (id, tenant_id, plan_id, status, expires_at)
invoices (id, tenant_id, amount, status, due_date)
system_analytics (tenant_id, metric_name, value, date)

-- RBAC for SuperAdmin
roles (id, name, permissions)
users (id, name, email, role_id, tenant_access)
```

#### Client Database (`cognify_client_{id}`)
```sql
-- Core entities
users (id, name, email, role, status)
students (id, user_id, enrollment_number, batch_id)
teachers (id, user_id, subjects, qualifications)
courses (id, name, description, teacher_id)
batches (id, course_id, name, start_date, end_date)

-- Academic management
assessments (id, course_id, type, questions, max_score)
submissions (id, assessment_id, student_id, answers, score)
attendance (id, student_id, batch_id, date, status)
grades (id, student_id, assessment_id, score, grade)

-- Gamification
badges (id, name, description, criteria, icon)
user_badges (id, user_id, badge_id, earned_at)
streaks (id, user_id, type, current_count, longest_count)
levels (id, user_id, current_level, total_xp)
points (id, user_id, activity_type, points, created_at)
leaderboards (id, type, period, rankings)

-- Communication
messages (id, sender_id, receiver_id, content, type)
announcements (id, title, content, target_audience)
notifications (id, user_id, type, content, read_at)

-- Financial
fee_structures (id, course_id, amount, installments)
payments (id, student_id, amount, status, payment_date)
invoices (id, student_id, amount, due_date, status)

-- CRM
leads (id, name, email, phone, status, source)
calls (id, lead_id, duration, recording_url, summary)
admissions (id, lead_id, course_id, status, documents)
```

### 3.3 Tenant Isolation & Security

```php
// Tenant-aware Model Base Class
abstract class TenantAwareModel extends Model
{
    protected $connection = 'tenant';
    
    public function newQuery()
    {
        // Ensure queries are always scoped to current tenant
        return parent::newQuery()->where('tenant_id', app('tenant')->id);
    }
}

// Automatic tenant scoping
class Student extends TenantAwareModel
{
    protected $fillable = ['name', 'email', 'enrollment_number'];
    
    protected static function booted()
    {
        static::creating(function ($model) {
            $model->tenant_id = app('tenant')->id;
        });
    }
}
```

---

## 4. Application Architecture & Structure

### 4.1 Laravel Application Structure

```
cognify/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── SuperAdmin/           # SuperAdmin controllers
│   │   │   ├── Client/               # Client portal controllers
│   │   │   └── API/                  # API controllers
│   │   ├── Middleware/
│   │   │   ├── TenantResolver.php
│   │   │   ├── SubscriptionCheck.php
│   │   │   └── FeatureGate.php
│   │   └── Requests/                 # Form validation
│   ├── Models/
│   │   ├── SuperAdmin/               # SuperAdmin models
│   │   ├── Tenant/                   # Tenant-specific models
│   │   └── Shared/                   # Shared models
│   ├── Services/
│   │   ├── AI/
│   │   │   ├── VoiceAssessmentService.php
│   │   │   ├── DoubtClearingService.php
│   │   │   └── RecommendationEngine.php
│   │   ├── Gamification/
│   │   │   ├── BadgeService.php
│   │   │   ├── StreakService.php
│   │   │   └── LeaderboardService.php
│   │   ├── Communication/
│   │   │   ├── NotificationService.php
│   │   │   ├── SMSService.php
│   │   │   └── EmailService.php
│   │   └── Payment/
│   │       ├── RazorpayService.php
│   │       └── StripeService.php
│   ├── Events/                       # Laravel events
│   ├── Listeners/                    # Event listeners
│   ├── Jobs/                         # Queue jobs
│   └── Packages/                     # Custom Laravel packages
├── database/
│   ├── migrations/
│   │   ├── superadmin/              # SuperAdmin migrations
│   │   └── tenant/                  # Tenant migrations
│   └── seeders/
├── resources/
│   ├── views/
│   │   ├── superadmin/              # SuperAdmin views
│   │   ├── client/                  # Client portal views
│   │   └── mobile/                  # PWA views
│   ├── js/                          # Frontend JavaScript
│   └── css/                         # Stylesheets
└── routes/
    ├── superadmin.php               # SuperAdmin routes
    ├── client.php                   # Client portal routes
    ├── api.php                      # API routes
    └── channels.php                 # Broadcasting routes
```

### 4.2 Modular Package Architecture

Each major module will be a Laravel package for maintainability:

```php
// Example: Gamification Package
namespace Cognify\Gamification;

class GamificationServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(BadgeService::class);
        $this->app->singleton(StreakService::class);
        $this->app->singleton(LeaderboardService::class);
    }
    
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
        $this->loadViewsFrom(__DIR__.'/resources/views', 'gamification');
        $this->publishes([
            __DIR__.'/config/gamification.php' => config_path('gamification.php'),
        ]);
    }
}
```

---

## 5. Core Module Architectures

### 5.1 Gamification Engine Architecture

```php
// Badge System
class BadgeService
{
    public function awardBadge(User $user, string $badgeType, array $criteria = []): ?UserBadge
    {
        $badge = Badge::where('type', $badgeType)->first();
        
        if (!$badge || $user->hasBadge($badge)) {
            return null;
        }
        
        if ($this->checkCriteria($user, $badge, $criteria)) {
            $userBadge = UserBadge::create([
                'user_id' => $user->id,
                'badge_id' => $badge->id,
                'earned_at' => now(),
            ]);
            
            // Fire event for real-time notification
            broadcast(new BadgeEarnedEvent($user, $badge));
            
            return $userBadge;
        }
        
        return null;
    }
}

// Streak System
class StreakService
{
    public function updateLoginStreak(User $user): void
    {
        $streak = $user->streaks()->where('type', 'login')->first();
        
        if (!$streak) {
            $streak = Streak::create([
                'user_id' => $user->id,
                'type' => 'login',
                'current_count' => 1,
                'longest_count' => 1,
                'last_activity' => now(),
            ]);
        } else {
            $lastActivity = $streak->last_activity;
            
            if ($lastActivity->isToday()) {
                return; // Already counted today
            }
            
            if ($lastActivity->isYesterday()) {
                // Continue streak
                $streak->increment('current_count');
                $streak->longest_count = max($streak->longest_count, $streak->current_count);
            } else {
                // Reset streak
                $streak->current_count = 1;
            }
            
            $streak->last_activity = now();
            $streak->save();
        }
        
        // Check for streak badges
        $this->checkStreakBadges($user, $streak);
    }
}
```

### 5.2 AI Integration Architecture

```php
// Voice Assessment Service
class VoiceAssessmentService
{
    private $speechToTextClient;
    private $openAIClient;
    
    public function processVoiceSubmission(Assessment $assessment, UploadedFile $audioFile): array
    {
        // 1. Upload audio file
        $audioPath = $audioFile->store('assessments/audio', 's3');
        
        // 2. Convert speech to text
        $transcription = $this->speechToTextClient->transcribe($audioPath);
        
        // 3. Grade the response using AI
        $gradingPrompt = $this->buildGradingPrompt($assessment, $transcription);
        $aiResponse = $this->openAIClient->completion($gradingPrompt);
        
        // 4. Parse AI response
        $result = $this->parseGradingResponse($aiResponse);
        
        return [
            'transcription' => $transcription,
            'score' => $result['score'],
            'feedback' => $result['feedback'],
            'audio_path' => $audioPath,
        ];
    }
    
    private function buildGradingPrompt(Assessment $assessment, string $transcription): string
    {
        return "
            Question: {$assessment->question}
            Expected Answer: {$assessment->expected_answer}
            Student Response: {$transcription}
            Max Score: {$assessment->max_score}
            
            Please grade this response and provide:
            1. Score (0-{$assessment->max_score})
            2. Brief feedback
            
            Format as JSON: {\"score\": X, \"feedback\": \"...\"}
        ";
    }
}

// Recommendation Engine
class RecommendationEngine
{
    public function getCourseRecommendations(User $student): Collection
    {
        // Analyze student's performance patterns
        $performanceData = $this->analyzeStudentPerformance($student);
        
        // Get similar students' successful courses
        $similarStudents = $this->findSimilarStudents($student, $performanceData);
        
        // Generate recommendations
        $recommendations = $this->generateRecommendations($similarStudents, $performanceData);
        
        return $recommendations;
    }
    
    private function analyzeStudentPerformance(User $student): array
    {
        return [
            'strong_subjects' => $student->getStrongSubjects(),
            'weak_subjects' => $student->getWeakSubjects(),
            'learning_pace' => $student->calculateLearningPace(),
            'engagement_level' => $student->calculateEngagementLevel(),
        ];
    }
}
```

### 5.3 Real-time Communication Architecture

```php
// Broadcasting Events
class StreakUpdatedEvent implements ShouldBroadcast
{
    public $user;
    public $streak;
    
    public function __construct(User $user, Streak $streak)
    {
        $this->user = $user;
        $this->streak = $streak;
    }
    
    public function broadcastOn()
    {
        return new PrivateChannel('user.' . $this->user->id);
    }
    
    public function broadcastWith()
    {
        return [
            'streak_type' => $this->streak->type,
            'current_count' => $this->streak->current_count,
            'message' => "🔥 {$this->streak->current_count} day streak!",
        ];
    }
}

// WebSocket Channel Authentication
Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('institute.{instituteId}', function ($user, $instituteId) {
    return $user->institute_id === (int) $instituteId;
});
```

---

## 6. Security Architecture

### 6.1 Multi-Tenant Security

```php
// Tenant Data Isolation
class TenantSecurityMiddleware
{
    public function handle($request, Closure $next)
    {
        $tenant = app('tenant');
        
        // Ensure all database queries are scoped to current tenant
        DB::listen(function ($query) use ($tenant) {
            if (!Str::contains($query->sql, 'tenant_id') && 
                !$this->isSystemTable($query->sql)) {
                throw new SecurityException('Query not properly scoped to tenant');
            }
        });
        
        return $next($request);
    }
}

// Feature Gate Middleware
class FeatureGateMiddleware
{
    public function handle($request, Closure $next, string $feature)
    {
        $tenant = app('tenant');
        
        if (!$tenant->subscription->hasFeature($feature)) {
            abort(403, 'Feature not available in your subscription plan');
        }
        
        return $next($request);
    }
}
```

### 6.2 Role-Based Access Control

```php
// Role and Permission System
class Role extends Model
{
    protected $fillable = ['name', 'permissions'];
    
    protected $casts = [
        'permissions' => 'array'
    ];
    
    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions ?? []);
    }
}

class User extends Authenticatable
{
    public function hasPermission(string $permission): bool
    {
        return $this->role?->hasPermission($permission) ?? false;
    }
    
    public function can($ability, $arguments = [])
    {
        // Custom permission logic for multi-tenant environment
        if ($this->hasPermission($ability)) {
            return true;
        }
        
        return parent::can($ability, $arguments);
    }
}

// Permission-based Route Protection
Route::middleware(['auth', 'permission:manage_students'])->group(function () {
    Route::resource('students', StudentController::class);
});
```

---

## 7. Performance & Scalability

### 7.1 Database Optimization

```php
// Optimized Query Examples
class StudentRepository
{
    public function getStudentsWithPerformance(int $batchId): Collection
    {
        return Student::with([
            'submissions.assessment',
            'badges.badge',
            'streaks',
            'attendance' => function ($query) {
                $query->where('date', '>=', now()->subMonth());
            }
        ])
        ->where('batch_id', $batchId)
        ->select(['id', 'name', 'email', 'enrollment_number'])
        ->get();
    }
    
    public function getLeaderboard(string $type, int $limit = 10): Collection
    {
        return Student::select(['students.id', 'students.name'])
            ->join('points', 'students.id', '=', 'points.user_id')
            ->selectRaw('SUM(points.points) as total_points')
            ->groupBy('students.id', 'students.name')
            ->orderByDesc('total_points')
            ->limit($limit)
            ->get();
    }
}
```

### 7.2 Caching Strategy

```php
// Redis Caching Implementation
class CacheService
{
    public function getStudentDashboard(User $student): array
    {
        $cacheKey = "student_dashboard_{$student->id}_" . now()->format('Y-m-d');
        
        return Cache::remember($cacheKey, 3600, function () use ($student) {
            return [
                'current_streaks' => $student->currentStreaks(),
                'recent_badges' => $student->recentBadges(5),
                'upcoming_assessments' => $student->upcomingAssessments(),
                'performance_summary' => $student->performanceSummary(),
            ];
        });
    }
    
    public function invalidateStudentCache(User $student): void
    {
        $pattern = "student_dashboard_{$student->id}_*";
        $keys = Redis::keys($pattern);
        
        if (!empty($keys)) {
            Redis::del($keys);
        }
    }
}

// Cache Invalidation on Model Updates
class Student extends TenantAwareModel
{
    protected static function booted()
    {
        static::updated(function ($student) {
            app(CacheService::class)->invalidateStudentCache($student);
        });
    }
}
```

### 7.3 Queue Architecture

```php
// Background Job Processing
class ProcessVoiceAssessmentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public $submission;
    public $audioFile;
    
    public function handle(VoiceAssessmentService $service)
    {
        $result = $service->processVoiceSubmission(
            $this->submission->assessment, 
            $this->audioFile
        );
        
        $this->submission->update([
            'transcription' => $result['transcription'],
            'score' => $result['score'],
            'feedback' => $result['feedback'],
            'status' => 'graded',
        ]);
        
        // Notify student
        $this->submission->student->notify(
            new AssessmentGradedNotification($this->submission)
        );
    }
}

// Queue Configuration
// config/queue.php
'connections' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => env('REDIS_QUEUE', 'default'),
        'retry_after' => 90,
        'block_for' => null,
    ],
],

'queues' => [
    'default' => 'default',
    'voice_processing' => 'voice_processing',
    'notifications' => 'notifications',
    'analytics' => 'analytics',
],
```

---

## 8. API Architecture

### 8.1 RESTful API Design

```php
// API Resource Controllers
class API\StudentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $students = Student::query()
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
            })
            ->when($request->batch_id, function ($query, $batchId) {
                $query->where('batch_id', $batchId);
            })
            ->paginate(20);
            
        return StudentResource::collection($students)->response();
    }
    
    public function show(Student $student): JsonResponse
    {
        $student->load(['badges.badge', 'streaks', 'currentBatch']);
        
        return new StudentResource($student);
    }
    
    public function dashboard(Student $student): JsonResponse
    {
        $dashboard = app(CacheService::class)->getStudentDashboard($student);
        
        return response()->json($dashboard);
    }
}

// API Resources
class StudentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'enrollment_number' => $this->enrollment_number,
            'current_level' => $this->currentLevel?->level,
            'total_xp' => $this->currentLevel?->total_xp,
            'active_streaks' => StreakResource::collection($this->whenLoaded('streaks')),
            'recent_badges' => BadgeResource::collection($this->whenLoaded('badges')),
            'performance_summary' => $this->when(
                $this->relationLoaded('submissions'),
                fn() => $this->calculatePerformanceSummary()
            ),
        ];
    }
}
```

### 8.2 API Authentication & Rate Limiting

```php
// API Authentication using Laravel Sanctum
class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();
        
        if (!Auth::attempt($credentials)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }
        
        $user = Auth::user();
        $token = $user->createToken('api-token', [
            'students:read',
            'assessments:create',
            'gamification:read',
        ])->plainTextToken;
        
        return response()->json([
            'user' => new UserResource($user),
            'token' => $token,
            'permissions' => $user->getAllPermissions(),
        ]);
    }
}

// Rate Limiting
// app/Providers/RouteServiceProvider.php
protected function configureRateLimiting()
{
    RateLimiter::for('api', function (Request $request) {
        return Limit::perMinute(60)->by(
            $request->user()?->id ?: $request->ip()
        );
    });
    
    RateLimiter::for('voice-processing', function (Request $request) {
        return Limit::perMinute(10)->by($request->user()->id);
    });
}
```

---

## 9. Mobile PWA Architecture

### 9.1 Progressive Web App Structure

```php
// PWA Service Provider
class PWAServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Generate manifest.json dynamically based on tenant
        Route::get('/manifest.json', function () {
            $tenant = app('tenant');
            
            return response()->json([
                'name' => $tenant->name . ' - Student Portal',
                'short_name' => $tenant->name,
                'start_url' => '/',
                'display' => 'standalone',
                'background_color' => $tenant->brand_color ?? '#6366f1',
                'theme_color' => $tenant->brand_color ?? '#6366f1',
                'icons' => [
                    [
                        'src' => $tenant->app_icon ?? '/images/default-icon-192.png',
                        'sizes' => '192x192',
                        'type' => 'image/png'
                    ],
                    [
                        'src' => $tenant->app_icon ?? '/images/default-icon-512.png',
                        'sizes' => '512x512',
                        'type' => 'image/png'
                    ]
                ]
            ]);
        });
    }
}
```

### 9.2 Offline Capability

```javascript
// Service Worker for Offline Support
// resources/js/sw.js
const CACHE_NAME = 'cognify-v1';
const OFFLINE_URLS = [
    '/',
    '/dashboard',
    '/assessments',
    '/badges',
    '/offline.html'
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => cache.addAll(OFFLINE_URLS))
    );
});

self.addEventListener('fetch', (event) => {
    if (event.request.method === 'GET') {
        event.respondWith(
            caches.match(event.request)
                .then((response) => {
                    // Return cached version or fetch from network
                    return response || fetch(event.request);
                })
                .catch(() => {
                    // Return offline page for navigation requests
                    if (event.request.mode === 'navigate') {
                        return caches.match('/offline.html');
                    }
                })
        );
    }
});
```

---

## 10. Deployment & DevOps

### 10.1 Environment Configuration

```yaml
# docker-compose.yml
version: '3.8'
services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: cognify-app
    environment:
      - APP_ENV=production
      - DB_CONNECTION=mysql
      - REDIS_HOST=redis
    volumes:
      - ./storage:/var/www/storage
    depends_on:
      - mysql
      - redis

  mysql:
    image: mysql:8.0
    container_name: cognify-mysql
    environment:
      MYSQL_ROOT_PASSWORD: ${DB_PASSWORD}
      MYSQL_DATABASE: cognify_superadmin
    volumes:
      - mysql_data:/var/lib/mysql

  redis:
    image: redis:7-alpine
    container_name: cognify-redis
    volumes:
      - redis_data:/data

  nginx:
    image: nginx:alpine
    container_name: cognify-nginx
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./nginx.conf:/etc/nginx/nginx.conf
    depends_on:
      - app

volumes:
  mysql_data:
  redis_data:
```

### 10.2 CI/CD Pipeline

```yaml
# .github/workflows/deploy.yml
name: Deploy Cognify

on:
  push:
    branches: [main]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.2
          extensions: mbstring, pdo_mysql, redis
      - name: Install dependencies
        run: composer install --no-dev --optimize-autoloader
      - name: Run tests
        run: php artisan test

  deploy:
    needs: test
    runs-on: ubuntu-latest
    steps:
      - name: Deploy to production
        run: |
          # Run database migrations for all tenants
          php artisan tenant:migrate
          # Clear caches
          php artisan config:cache
          php artisan route:cache
          php artisan view:cache
          # Restart queue workers
          php artisan queue:restart
```

---

## 11. Monitoring & Analytics

### 11.1 Application Monitoring

```php
// Custom Monitoring Service
class MonitoringService
{
    public function trackUserActivity(User $user, string $activity, array $metadata = []): void
    {
        ActivityLog::create([
            'user_id' => $user->id,
            'tenant_id' => app('tenant')->id,
            'activity' => $activity,
            'metadata' => $metadata,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }
    
    public function trackSystemMetric(string $metric, $value, string $tenant = null): void
    {
        SystemMetric::create([
            'tenant_id' => $tenant ?? app('tenant')?->id,
            'metric_name' => $metric,
            'value' => $value,
            'timestamp' => now(),
        ]);
    }
}

// Performance Monitoring Middleware
class PerformanceMonitoringMiddleware
{
    public function handle($request, Closure $next)
    {
        $startTime = microtime(true);
        
        $response = $next($request);
        
        $executionTime = (microtime(true) - $startTime) * 1000;
        
        // Log slow queries
        if ($executionTime > 500) {
            Log::warning('Slow request detected', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'execution_time' => $executionTime,
                'tenant' => app('tenant')?->name,
            ]);
        }
        
        return $response;
    }
}
```

---

## 12. Security Considerations

### 12.1 Data Protection & Privacy

```php
// GDPR Compliance Service
class DataProtectionService
{
    public function exportUserData(User $user): array
    {
        return [
            'personal_info' => $user->only(['name', 'email', 'phone']),
            'academic_data' => $user->submissions()->with('assessment')->get(),
            'gamification_data' => [
                'badges' => $user->badges()->with('badge')->get(),
                'streaks' => $user->streaks,
                'points' => $user->points,
            ],
            'communication_data' => $user->messages,
        ];
    }
    
    public function anonymizeUserData(User $user): void
    {
        $user->update([
            'name' => 'Anonymous User',
            'email' => 'deleted_' . Str::random(10) . '@example.com',
            'phone' => null,
            'deleted_at' => now(),
        ]);
        
        // Keep academic data for analytics but anonymize
        $user->submissions()->update(['student_name' => 'Anonymous']);
    }
}
```

### 12.2 Input Validation & Sanitization

```php
// Custom Validation Rules
class VoiceFileValidationRule implements Rule
{
    public function passes($attribute, $value): bool
    {
        if (!$value instanceof UploadedFile) {
            return false;
        }
        
        // Check file type
        $allowedMimes = ['audio/mpeg', 'audio/wav', 'audio/mp3'];
        if (!in_array($value->getMimeType(), $allowedMimes)) {
            return false;
        }
        
        // Check file size (max 10MB)
        if ($value->getSize() > 10 * 1024 * 1024) {
            return false;
        }
        
        // Check duration (max 5 minutes)
        if ($this->getAudioDuration($value) > 300) {
            return false;
        }
        
        return true;
    }
    
    public function message(): string
    {
        return 'The audio file must be a valid format (MP3, WAV), under 10MB, and less than 5 minutes long.';
    }
}
```

---

## 13. Conclusion

This technical architecture provides a comprehensive foundation for building Cognify as a scalable, secure, and feature-rich multi-tenant SaaS platform using PHP and Laravel. The architecture emphasizes:

1. **Scalability** through proper multi-tenant design and caching strategies
2. **Security** through tenant isolation and robust RBAC
3. **Performance** through optimized queries, caching, and queue processing
4. **Maintainability** through modular package structure and clean code principles
5. **Extensibility** through well-defined interfaces and service architecture

The modular approach allows for incremental development following the 4-phase roadmap outlined in the PRD, while the Laravel framework provides the stability and ecosystem needed for rapid development and deployment.

---

**Next Steps for Implementation:**
1. Set up development environment with multi-tenant Laravel structure
2. Implement core SuperAdmin system and tenant management
3. Develop tenant database migration and seeding system
4. Create basic authentication and RBAC system
5. Begin Phase 1 module development (SIS, CRM, basic gamification)

*Architecture Status: Ready for Development*
