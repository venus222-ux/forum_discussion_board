Forum_discussion_board\
├── backend/ # Laravel 12 API
└── frontend/ # React + Vite + TS SPA

✅ 2. Set Up Laravel Backend
cd backend
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan serve

php artisan jwt:secret
php artisan config:clear
php artisan config:cache

✅ 3. Set Up React Frontend
cd ../frontend
cp .env.example .env
npm install
npm run dev

✅ 4. Run in the root project:
npm run dev
docker-compose up -d

1. Product Vision
   A scalable, production-ready discussion platform inspired by Reddit and StackOverflow. Designed with a modern full-stack architecture focused on performance, real-time interaction, and horizontal scalability.

- Categories & Threads: Organized discussion spaces.
- Infinite Nesting: Deeply nested comment trees without performance degradation.
- Real-time Engine: Instant notifications (mentions/replies) via Pusher.
- Elite Search: Full-text search powered by Elasticsearch.
- Polyglot Persistence: Separating structured relational data (MySQL) from flexible discussion data (MongoDB).

2. Tech Architecture
   React
   TypeScript
   Vite
   Zustand
   TanStack Query
   React Hook Form

Laravel API
Redis
Elasticsearch
MySQL
MongoDB

3. Data Design
   Mysql: users, categories, threads, votes, reports
   MongoDB (Comments Collection)

4. Core Features (Backlog)
   Authentication: JWT login/register + Role-Based Access Control (Admin/Mod/User).

Threads: Full CRUD with pagination and category filtering.

Comments: Recursive tree structure, upvote/downvote system, and @username mentions.

Moderation: Content reporting, thread locking, and user banning tools.

Notifications: Real-time push for replies and mentions.
