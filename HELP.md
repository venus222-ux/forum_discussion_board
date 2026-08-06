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
