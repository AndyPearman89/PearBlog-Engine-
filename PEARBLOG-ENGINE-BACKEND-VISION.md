# 🚀 PEARBLOG ENGINE — BACKEND (PRODUCTION READY, STANDALONE)

**Wersja:** 1.0
**Data:** 2026-05-04
**Status:** Strategic Vision & Architecture Blueprint

---

## 🎯 CEL

Zbudować backend CMS + Content Engine, który:

- **obsługuje Poradnik.pro** (content + SEO)
- **zasila PT24.pro** (leady + eksperci)
- **działa jako headless API**

### 👉 To NIE jest WordPress theme
### 👉 To jest **Content Operating System**

---

## 📊 OBECNY STAN vs WIZJA

### Obecna Implementacja (v8.0)
- **Platform**: WordPress Plugin (PHP)
- **Struktura**: MU-Plugin z PSR-4 autoloading
- **Baza danych**: MySQL (WordPress tables)
- **Cache**: Transient API + opcjonalnie Redis
- **Queue**: WP-Cron
- **API**: WordPress REST API

### Wizja Backend (Standalone)
- **Platform**: Node.js + NestJS
- **Struktura**: Modular monolith z DDD
- **Baza danych**: PostgreSQL
- **Cache**: Redis
- **Queue**: BullMQ
- **API**: REST + opcjonalnie GraphQL

---

# 🧠 ARCHITEKTURA

## 🧱 WARSTWY (LAYERED ARCHITECTURE)

```
┌─────────────────────────────────────────┐
│       API Layer (REST/GraphQL)          │
│  - Controllers                          │
│  - Validation                           │
│  - Authentication                       │
└─────────────────────────────────────────┘
                  ↓
┌─────────────────────────────────────────┐
│    Application Layer (Use Cases)        │
│  - CreateArticle                        │
│  - ProcessContent                       │
│  - SyncToPT24                          │
│  - GenerateSEO                         │
└─────────────────────────────────────────┘
                  ↓
┌─────────────────────────────────────────┐
│       Domain Layer (Entities)           │
│  - Article                              │
│  - Block                                │
│  - Expert                               │
│  - Lead                                 │
└─────────────────────────────────────────┘
                  ↓
┌─────────────────────────────────────────┐
│   Infrastructure (DB, Cache, Queue)     │
│  - PostgreSQL                           │
│  - Redis                                │
│  - BullMQ                              │
│  - External APIs                        │
└─────────────────────────────────────────┘
```

---

## ⚙️ STACK (REKOMENDOWANY)

### Backend Core
- **Node.js** v20+ LTS
- **NestJS** - Framework (TypeScript, DI, modules)
- **TypeORM** - ORM dla PostgreSQL
- **PostgreSQL** 15+ - Primary database
- **Redis** 7+ - Cache + Sessions

### Queue & Jobs
- **BullMQ** - Job queue
- **Redis** - Queue backend

### API
- **REST API** - Primary (NestJS controllers)
- **GraphQL** - Optional (Apollo Server)
- **Swagger/OpenAPI** - Auto-generated docs

### AI Integration
- **OpenAI SDK** - GPT-4o, DALL-E 3
- **Langchain** - Optional for complex flows

### Monitoring
- **Prometheus** - Metrics
- **Grafana** - Dashboards
- **Winston** - Logging
- **Sentry** - Error tracking

---

# 📦 STRUKTURA PROJEKTU

```
pearblog-backend/
├── src/
│   ├── modules/
│   │   ├── article/
│   │   │   ├── article.module.ts
│   │   │   ├── article.controller.ts
│   │   │   ├── article.service.ts
│   │   │   ├── article.entity.ts
│   │   │   ├── article.repository.ts
│   │   │   └── dto/
│   │   │       ├── create-article.dto.ts
│   │   │       └── update-article.dto.ts
│   │   │
│   │   ├── block/
│   │   │   ├── block.module.ts
│   │   │   ├── block.service.ts
│   │   │   ├── block.entity.ts
│   │   │   └── block-builder.service.ts
│   │   │
│   │   ├── seo/
│   │   │   ├── seo.module.ts
│   │   │   ├── seo-engine.service.ts
│   │   │   ├── schema-generator.service.ts
│   │   │   └── meta-optimizer.service.ts
│   │   │
│   │   ├── ai/
│   │   │   ├── ai.module.ts
│   │   │   ├── ai-client.service.ts
│   │   │   ├── content-analyzer.service.ts
│   │   │   ├── intent-detector.service.ts
│   │   │   └── image-generator.service.ts
│   │   │
│   │   ├── integration/
│   │   │   ├── integration.module.ts
│   │   │   ├── pt24-client.service.ts
│   │   │   └── sync.service.ts
│   │   │
│   │   ├── lead/
│   │   │   ├── lead.module.ts
│   │   │   ├── lead.controller.ts
│   │   │   ├── lead.service.ts
│   │   │   └── lead.entity.ts
│   │   │
│   │   ├── expert/
│   │   │   ├── expert.module.ts
│   │   │   ├── expert.controller.ts
│   │   │   ├── expert.service.ts
│   │   │   └── expert.entity.ts
│   │   │
│   │   └── user/
│   │       ├── user.module.ts
│   │       ├── user.controller.ts
│   │       ├── user.service.ts
│   │       └── user.entity.ts
│   │
│   ├── shared/
│   │   ├── database/
│   │   │   ├── database.module.ts
│   │   │   └── database.config.ts
│   │   │
│   │   ├── cache/
│   │   │   ├── cache.module.ts
│   │   │   └── cache.service.ts
│   │   │
│   │   ├── queue/
│   │   │   ├── queue.module.ts
│   │   │   ├── queue.service.ts
│   │   │   └── processors/
│   │   │       ├── article-processor.ts
│   │   │       ├── seo-processor.ts
│   │   │       └── sync-processor.ts
│   │   │
│   │   ├── config/
│   │   │   └── configuration.ts
│   │   │
│   │   └── utils/
│   │       ├── logger.ts
│   │       └── validators.ts
│   │
│   ├── app.module.ts
│   └── main.ts
│
├── test/
├── dist/
├── .env.example
├── package.json
├── tsconfig.json
├── nest-cli.json
└── README.md
```

---

# 📄 MODELE DANYCH

## Article Entity

```typescript
// src/modules/article/article.entity.ts

import { Entity, PrimaryGeneratedColumn, Column, CreateDateColumn, UpdateDateColumn, OneToMany } from 'typeorm';
import { Block } from '../block/block.entity';

export enum ArticleIntent {
  INFORMATIONAL = 'informational',
  COMMERCIAL = 'commercial',
  TRANSACTIONAL = 'transactional',
}

@Entity('articles')
export class Article {
  @PrimaryGeneratedColumn()
  id: number;

  @Column({ length: 255 })
  title: string;

  @Column({ length: 255, unique: true })
  slug: string;

  @Column('text')
  content_raw: string;

  @Column('jsonb', { nullable: true })
  blocks: any;

  @Column({
    type: 'enum',
    enum: ArticleIntent,
    default: ArticleIntent.INFORMATIONAL,
  })
  intent: ArticleIntent;

  @Column({ length: 100 })
  category: string;

  @Column({ length: 100, nullable: true })
  city?: string;

  @Column('jsonb', { nullable: true })
  seo: {
    title: string;
    description: string;
    keywords: string[];
    schema: any;
  };

  @Column({ default: 'draft' })
  status: string;

  @Column({ type: 'float', default: 0 })
  quality_score: number;

  @Column({ default: false })
  published: boolean;

  @CreateDateColumn()
  created_at: Date;

  @UpdateDateColumn()
  updated_at: Date;

  @OneToMany(() => Block, block => block.article)
  blockEntities: Block[];
}
```

---

## Block Entity

```typescript
// src/modules/block/block.entity.ts

import { Entity, PrimaryGeneratedColumn, Column, ManyToOne } from 'typeorm';
import { Article } from '../article/article.entity';

export enum BlockType {
  INTRO = 'intro',
  FAQ = 'faq',
  COMPARISON = 'comparison',
  RANKING = 'ranking',
  LEAD_FORM = 'lead_form',
  EXPERTS = 'experts',
  CTA = 'cta',
  CALCULATOR = 'calculator',
}

@Entity('blocks')
export class Block {
  @PrimaryGeneratedColumn()
  id: number;

  @Column({
    type: 'enum',
    enum: BlockType,
  })
  type: BlockType;

  @Column('jsonb')
  data: any;

  @Column({ default: 0 })
  position: number;

  @ManyToOne(() => Article, article => article.blockEntities)
  article: Article;
}
```

---

## Lead Entity

```typescript
// src/modules/lead/lead.entity.ts

import { Entity, PrimaryGeneratedColumn, Column, CreateDateColumn } from 'typeorm';

@Entity('leads')
export class Lead {
  @PrimaryGeneratedColumn()
  id: number;

  @Column({ length: 100 })
  name: string;

  @Column({ length: 100 })
  email: string;

  @Column({ length: 20 })
  phone: string;

  @Column('text', { nullable: true })
  message?: string;

  @Column({ length: 100 })
  category: string;

  @Column({ length: 100 })
  city: string;

  @Column({ length: 100 })
  source: string; // 'poradnik', 'pt24', 'direct'

  @Column({ default: 'new' })
  status: string;

  @CreateDateColumn()
  created_at: Date;
}
```

---

## Expert Entity

```typescript
// src/modules/expert/expert.entity.ts

import { Entity, PrimaryGeneratedColumn, Column, CreateDateColumn } from 'typeorm';

@Entity('experts')
export class Expert {
  @PrimaryGeneratedColumn()
  id: number;

  @Column({ length: 255 })
  name: string;

  @Column({ length: 255, unique: true })
  slug: string;

  @Column({ length: 100 })
  category: string;

  @Column({ length: 100 })
  city: string;

  @Column('text', { nullable: true })
  description?: string;

  @Column({ length: 20 })
  phone: string;

  @Column({ length: 100, nullable: true })
  email?: string;

  @Column('jsonb', { nullable: true })
  services: string[];

  @Column({ type: 'float', default: 0 })
  rating: number;

  @Column({ default: true })
  active: boolean;

  @CreateDateColumn()
  created_at: Date;
}
```

---

# 🧠 CORE FLOW

## 1. CREATE ARTICLE

```
Editor/API → POST /api/articles
  ↓
Validation (DTO)
  ↓
Save draft to DB
  ↓
Trigger processing queue
  ↓
Return article ID + status
```

### Example Request

```typescript
POST /api/articles
Content-Type: application/json

{
  "title": "Ile kosztuje hydraulik w Krakowie?",
  "content_raw": "Szukasz hydraulika w Krakowie? Sprawdź ceny...",
  "category": "hydraulik",
  "city": "kraków"
}
```

---

## 2. PROCESSING PIPELINE (Queue Job)

```
Queue: "process_article" job
  ↓
1. AI Analyze content_raw
  ↓
2. Intent Detection (info/commercial/transactional)
  ↓
3. Block Builder (generate structured blocks)
  ↓
4. SEO Generator (title, description, schema)
  ↓
5. PT24 Integration (fetch experts if applicable)
  ↓
6. Save blocks + SEO to DB
  ↓
7. Quality Score calculation
  ↓
8. Auto-publish if score > threshold
```

### Pipeline Service

```typescript
// src/modules/article/article-pipeline.service.ts

import { Injectable } from '@nestjs/common';
import { InjectQueue } from '@nestjs/bull';
import { Queue } from 'bull';

@Injectable()
export class ArticlePipelineService {
  constructor(
    @InjectQueue('article-processing') private articleQueue: Queue,
    private aiService: AIService,
    private blockBuilder: BlockBuilderService,
    private seoEngine: SEOEngineService,
    private pt24Client: PT24ClientService,
  ) {}

  async processArticle(articleId: number): Promise<void> {
    // Add to queue for async processing
    await this.articleQueue.add('process', { articleId });
  }

  async handleProcessJob(articleId: number): Promise<void> {
    const article = await this.articleRepository.findOne(articleId);

    // 1. Detect intent
    const intent = await this.aiService.detectIntent(article.content_raw);
    article.intent = intent;

    // 2. Build blocks
    const blocks = await this.blockBuilder.buildBlocks(article);
    article.blocks = blocks;

    // 3. Generate SEO
    const seo = await this.seoEngine.generateSEO(article);
    article.seo = seo;

    // 4. PT24 Integration (if transactional)
    if (intent === 'transactional' && article.city) {
      const experts = await this.pt24Client.fetchExperts({
        category: article.category,
        city: article.city,
      });

      blocks.push({
        type: 'experts',
        data: { experts },
      });
    }

    // 5. Calculate quality score
    article.quality_score = this.calculateQualityScore(article);

    // 6. Auto-publish if high quality
    if (article.quality_score >= 0.8) {
      article.status = 'published';
      article.published = true;
    }

    await this.articleRepository.save(article);
  }
}
```

---

## 3. GET ARTICLE (API)

```
GET /api/articles/{slug}
  ↓
Fetch from cache (Redis)
  ↓
If not cached, fetch from DB
  ↓
Return: article + blocks + SEO + data
  ↓
Cache for 1 hour
```

### Example Response

```json
{
  "id": 123,
  "title": "Ile kosztuje hydraulik w Krakowie?",
  "slug": "ile-kosztuje-hydraulik-w-krakowie",
  "content_raw": "...",
  "blocks": [
    {
      "type": "intro",
      "data": {
        "text": "Szukasz hydraulika w Krakowie? Ceny..."
      }
    },
    {
      "type": "faq",
      "data": {
        "questions": [
          {
            "q": "Ile kosztuje podstawowa naprawa?",
            "a": "Od 150 do 300 zł..."
          }
        ]
      }
    },
    {
      "type": "experts",
      "data": {
        "experts": [
          {
            "name": "Jan Kowalski",
            "phone": "+48 123 456 789",
            "rating": 4.8
          }
        ]
      }
    }
  ],
  "seo": {
    "title": "Ile kosztuje hydraulik w Krakowie? Ceny 2026",
    "description": "Sprawdź aktualne ceny hydraulików w Krakowie...",
    "keywords": ["hydraulik kraków", "ceny hydraulik", "naprawa"],
    "schema": { "@type": "Article", "..." }
  },
  "intent": "transactional",
  "category": "hydraulik",
  "city": "kraków",
  "quality_score": 0.85,
  "created_at": "2026-05-04T10:00:00Z"
}
```

---

# 📡 API ENDPOINTS

## Articles

```typescript
// Article endpoints
POST   /api/articles              // Create article
GET    /api/articles              // List articles (paginated)
GET    /api/articles/:id          // Get article by ID
GET    /api/articles/slug/:slug   // Get article by slug
PUT    /api/articles/:id          // Update article
DELETE /api/articles/:id          // Delete article
GET    /api/articles/:id/blocks   // Get article blocks
POST   /api/articles/:id/process  // Trigger processing
POST   /api/articles/:id/publish  // Publish article
```

## Leads

```typescript
// Lead endpoints
POST   /api/leads                 // Create lead
GET    /api/leads                 // List leads
GET    /api/leads/:id             // Get lead
PUT    /api/leads/:id             // Update lead status
```

## Experts

```typescript
// Expert endpoints
POST   /api/experts               // Create expert
GET    /api/experts               // List experts (filtered)
GET    /api/experts/:id           // Get expert
PUT    /api/experts/:id           // Update expert
GET    /api/experts/search        // Search experts
  // ?category=hydraulik&city=kraków
```

## PT24 Integration

```typescript
// PT24 sync endpoints
POST   /api/sync/pt24             // Sync article to PT24
POST   /api/sync/pt24/experts     // Sync experts from PT24
GET    /api/sync/status           // Get sync status
```

## SEO

```typescript
// SEO endpoints
POST   /api/seo/analyze           // Analyze content
POST   /api/seo/generate          // Generate SEO meta
GET    /api/seo/keywords          // Get keyword suggestions
```

---

# 🤖 AI ENGINE

## Services

### Intent Detector

```typescript
// src/modules/ai/intent-detector.service.ts

import { Injectable } from '@nestjs/common';
import { OpenAI } from 'openai';

@Injectable()
export class IntentDetectorService {
  constructor(private openai: OpenAI) {}

  async detectIntent(title: string, content: string): Promise<ArticleIntent> {
    const prompt = `
Analyze this article and classify the search intent:
- informational: user wants to learn
- commercial: user is comparing options
- transactional: user is ready to buy/contact

Title: ${title}
Content: ${content.substring(0, 500)}

Respond with only one word: informational, commercial, or transactional
`;

    const response = await this.openai.chat.completions.create({
      model: 'gpt-4o-mini',
      messages: [{ role: 'user', content: prompt }],
      temperature: 0.3,
    });

    const intent = response.choices[0].message.content.trim().toLowerCase();
    return intent as ArticleIntent;
  }
}
```

---

### Block Builder

```typescript
// src/modules/block/block-builder.service.ts

@Injectable()
export class BlockBuilderService {
  async buildBlocks(article: Article): Promise<any[]> {
    const blocks = [];

    // 1. Always add intro
    blocks.push({
      type: 'intro',
      data: await this.generateIntro(article),
    });

    // 2. Add FAQ if informational or commercial
    if (['informational', 'commercial'].includes(article.intent)) {
      blocks.push({
        type: 'faq',
        data: await this.generateFAQ(article),
      });
    }

    // 3. Add lead form if transactional
    if (article.intent === 'transactional') {
      blocks.push({
        type: 'lead_form',
        data: {
          title: 'Szukasz specjalisty?',
          cta: 'Wyślij zapytanie',
          fields: ['name', 'phone', 'message'],
        },
      });
    }

    // 4. Add calculator if relevant
    if (this.shouldAddCalculator(article)) {
      blocks.push({
        type: 'calculator',
        data: await this.generateCalculator(article),
      });
    }

    return blocks;
  }

  private async generateFAQ(article: Article): Promise<any> {
    const prompt = `
Generate 5 FAQ questions and answers for this article:
Title: ${article.title}
Category: ${article.category}

Return as JSON array: [{"q": "...", "a": "..."}]
`;

    const response = await this.openai.chat.completions.create({
      model: 'gpt-4o-mini',
      messages: [{ role: 'user', content: prompt }],
      response_format: { type: 'json_object' },
    });

    return JSON.parse(response.choices[0].message.content);
  }
}
```

---

# 🔗 INTEGRATION WITH PT24

## PT24 Client Service

```typescript
// src/modules/integration/pt24-client.service.ts

@Injectable()
export class PT24ClientService {
  constructor(private httpService: HttpService) {}

  async fetchExperts(params: {
    category: string;
    city: string;
  }): Promise<Expert[]> {
    const url = `${process.env.PT24_API_URL}/experts`;

    const response = await this.httpService.get(url, {
      params: {
        category: params.category,
        city: params.city,
        limit: 5,
      },
    }).toPromise();

    return response.data.experts;
  }

  async syncArticle(article: Article): Promise<void> {
    // Sync article metadata to PT24 for cross-platform linking
    await this.httpService.post(
      `${process.env.PT24_API_URL}/sync/article`,
      {
        article_id: article.id,
        slug: article.slug,
        category: article.category,
        city: article.city,
      },
    ).toPromise();
  }

  async sendLead(lead: Lead): Promise<void> {
    // Forward lead to PT24 for expert matching
    await this.httpService.post(
      `${process.env.PT24_API_URL}/leads`,
      lead,
    ).toPromise();
  }
}
```

---

## Matching Logic

```typescript
// Category mapping: Poradnik.pro → PT24.pro
const CATEGORY_MAP = {
  'hydraulik': 'hydraulik',
  'elektryk': 'elektryk-samochodowy',
  'mechanik': 'mechanik-samochodowy',
  'laweta': 'laweta',
};

// City normalization
function normalizeCity(city: string): string {
  return city
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, ''); // Remove diacritics
}
```

---

# ⚡ QUEUE SYSTEM

## Queue Configuration

```typescript
// src/shared/queue/queue.module.ts

import { BullModule } from '@nestjs/bull';

@Module({
  imports: [
    BullModule.forRoot({
      redis: {
        host: process.env.REDIS_HOST,
        port: parseInt(process.env.REDIS_PORT),
      },
    }),
    BullModule.registerQueue({
      name: 'article-processing',
    }),
    BullModule.registerQueue({
      name: 'seo-refresh',
    }),
    BullModule.registerQueue({
      name: 'pt24-sync',
    }),
  ],
})
export class QueueModule {}
```

---

## Job Processor

```typescript
// src/shared/queue/processors/article-processor.ts

import { Process, Processor } from '@nestjs/bull';
import { Job } from 'bull';

@Processor('article-processing')
export class ArticleProcessor {
  constructor(
    private articlePipeline: ArticlePipelineService,
  ) {}

  @Process('process')
  async handleProcess(job: Job): Promise<void> {
    const { articleId } = job.data;

    await this.articlePipeline.handleProcessJob(articleId);

    // Update job progress
    await job.progress(100);
  }

  @Process('seo-refresh')
  async handleSEORefresh(job: Job): Promise<void> {
    const { articleId } = job.data;
    // Refresh SEO metadata
  }
}
```

---

# 🔐 AUTHENTICATION & AUTHORIZATION

## JWT Strategy

```typescript
// src/modules/user/jwt.strategy.ts

import { Injectable } from '@nestjs/common';
import { PassportStrategy } from '@nestjs/passport';
import { ExtractJwt, Strategy } from 'passport-jwt';

@Injectable()
export class JwtStrategy extends PassportStrategy(Strategy) {
  constructor() {
    super({
      jwtFromRequest: ExtractJwt.fromAuthHeaderAsBearerToken(),
      secretOrKey: process.env.JWT_SECRET,
    });
  }

  async validate(payload: any) {
    return {
      userId: payload.sub,
      email: payload.email,
      role: payload.role,
    };
  }
}
```

---

## Roles

```typescript
export enum UserRole {
  ADMIN = 'admin',       // Full system access
  EDITOR = 'editor',     // Content management
  SYSTEM = 'system',     // API automation access
}
```

---

## Guards

```typescript
// src/shared/guards/roles.guard.ts

@Injectable()
export class RolesGuard implements CanActivate {
  constructor(private reflector: Reflector) {}

  canActivate(context: ExecutionContext): boolean {
    const requiredRoles = this.reflector.get<UserRole[]>(
      'roles',
      context.getHandler(),
    );

    if (!requiredRoles) {
      return true;
    }

    const request = context.switchToHttp().getRequest();
    const user = request.user;

    return requiredRoles.some((role) => user.role === role);
  }
}
```

---

# 📊 CACHE STRATEGY

## Cache Service

```typescript
// src/shared/cache/cache.service.ts

@Injectable()
export class CacheService {
  constructor(@Inject(CACHE_MANAGER) private cacheManager: Cache) {}

  async getArticle(slug: string): Promise<Article | null> {
    const key = `article:${slug}`;
    return await this.cacheManager.get(key);
  }

  async setArticle(slug: string, article: Article): Promise<void> {
    const key = `article:${slug}`;
    await this.cacheManager.set(key, article, { ttl: 3600 }); // 1 hour
  }

  async invalidateArticle(slug: string): Promise<void> {
    const key = `article:${slug}`;
    await this.cacheManager.del(key);
  }
}
```

---

## Cache Keys Pattern

```
article:{slug}           → Full article data (1h TTL)
article:{id}:blocks      → Article blocks (1h TTL)
experts:{category}:{city} → Expert list (15m TTL)
seo:keywords:{query}     → Keyword suggestions (24h TTL)
```

---

# 📈 SCALING STRATEGY

## Horizontal Scaling

- **Stateless API servers** - no session storage in memory
- **Redis for session/cache** - shared across instances
- **PostgreSQL with read replicas** - distribute read load
- **Load balancer** (nginx/HAProxy) - distribute traffic

---

## Architecture Diagram

```
                    ┌──────────────┐
                    │ Load Balancer│
                    └──────┬───────┘
                           │
          ┌────────────────┼────────────────┐
          │                │                │
    ┌─────▼─────┐    ┌─────▼─────┐   ┌─────▼─────┐
    │  API #1   │    │  API #2   │   │  API #3   │
    │ (NestJS)  │    │ (NestJS)  │   │ (NestJS)  │
    └─────┬─────┘    └─────┬─────┘   └─────┬─────┘
          │                │                │
          └────────────────┼────────────────┘
                           │
          ┌────────────────┼────────────────┐
          │                │                │
    ┌─────▼─────┐    ┌─────▼─────┐   ┌─────▼─────┐
    │PostgreSQL │    │   Redis   │   │  BullMQ   │
    │  Primary  │    │   Cache   │   │   Queue   │
    └─────┬─────┘    └───────────┘   └───────────┘
          │
    ┌─────▼─────┐
    │PostgreSQL │
    │  Replica  │
    └───────────┘
```

---

## Performance Targets

- **API Response Time**: < 100ms (cached), < 500ms (DB)
- **Article Processing**: < 60 seconds
- **Throughput**: 1000 req/s per API instance
- **Availability**: 99.9% uptime

---

# 🔥 DEPLOYMENT

## Docker Compose

```yaml
# docker-compose.yml

version: '3.8'

services:
  api:
    build: .
    ports:
      - "3000:3000"
    environment:
      - DATABASE_URL=postgresql://user:pass@postgres:5432/pearblog
      - REDIS_HOST=redis
      - REDIS_PORT=6379
      - OPENAI_API_KEY=${OPENAI_API_KEY}
    depends_on:
      - postgres
      - redis

  postgres:
    image: postgres:15
    environment:
      - POSTGRES_DB=pearblog
      - POSTGRES_USER=user
      - POSTGRES_PASSWORD=pass
    volumes:
      - postgres_data:/var/lib/postgresql/data

  redis:
    image: redis:7-alpine
    ports:
      - "6379:6379"

  worker:
    build: .
    command: npm run worker
    environment:
      - DATABASE_URL=postgresql://user:pass@postgres:5432/pearblog
      - REDIS_HOST=redis
      - REDIS_PORT=6379
    depends_on:
      - postgres
      - redis

volumes:
  postgres_data:
```

---

## Environment Variables

```bash
# .env.example

# Database
DATABASE_URL=postgresql://user:pass@localhost:5432/pearblog

# Redis
REDIS_HOST=localhost
REDIS_PORT=6379

# API
PORT=3000
JWT_SECRET=your-secret-key-here

# OpenAI
OPENAI_API_KEY=sk-...

# PT24 Integration
PT24_API_URL=https://api.pt24.pro
PT24_API_KEY=your-pt24-api-key

# Monitoring
SENTRY_DSN=https://...
```

---

# 🧠 FINAL SYSTEM OVERVIEW

## PearBlog Engine jako Content Operating System

```
┌─────────────────────────────────────────────────────────┐
│                    PEARBLOG ENGINE                      │
│              Content Operating System                    │
└─────────────────────────────────────────────────────────┘
                            │
        ┌───────────────────┼───────────────────┐
        │                   │                   │
  ┌─────▼─────┐      ┌──────▼──────┐     ┌─────▼─────┐
  │ Poradnik  │      │    PT24     │     │  External │
  │   .pro    │      │    .pro     │     │    APIs   │
  └───────────┘      └─────────────┘     └───────────┘

   Content Hub       Lead Platform      Integrations
```

---

## What It Does

👉 **Tworzy content** - AI-powered article generation
👉 **Analizuje** - Intent detection, quality scoring
👉 **Buduje strukturę** - Modular block system
👉 **Integruje PT24** - Expert matching, lead routing
👉 **Generuje SEO** - Automated meta, schema, optimization
👉 **Skaluje** - Horizontal scaling, caching, queue

---

# 🚀 MIGRATION PATH

## Od WordPress Plugin do Standalone Backend

### Faza 1: Hybrid (3-6 miesięcy)
- ✅ **Obecny stan**: WordPress Plugin działa w produkcji
- 🔄 **Backend NestJS**: Równoległy development
- 🔄 **API parity**: Implementacja kluczowych endpointów
- 🔄 **Testing**: Comprehensive test coverage

### Faza 2: Parallel Run (2-3 miesiące)
- 🔄 **Dual mode**: WordPress + Backend działają równolegle
- 🔄 **Data sync**: Dwukierunkowa synchronizacja danych
- 🔄 **Traffic split**: 10% → 50% → 100% na nowy backend
- 🔄 **Monitoring**: Porównanie metryk, błędów, performance

### Faza 3: Full Migration (1-2 miesiące)
- 🔄 **100% traffic** na nowy backend
- 🔄 **WordPress jako admin UI** (opcjonalnie)
- 🔄 **Decommission**: Wyłączenie starego systemu
- ✅ **Production**: Standalone backend w pełni operacyjny

---

## Koszty i ROI

### Investment
- **Development**: 3-6 dev-months
- **Infrastructure**: $200-500/month (AWS/DO)
- **Migration**: 1-2 dev-months

### Benefits
- **Performance**: 5-10x faster API responses
- **Scalability**: Horizontal scaling bez limitów WP
- **Flexibility**: Niezależność od WordPress ecosystem
- **Maintenance**: Łatwiejsze utrzymanie i rozwój

---

# 🔥 PODSUMOWANIE

## To NIE jest CMS

PearBlog Engine to:

### 👉 AI-driven Content Engine
Autonomiczne generowanie, analiza i optymalizacja treści

### 👉 Revenue Driver
Integracja leadów, ekspertów, monetyzacja

### 👉 Content Operating System
Kompletna platforma do zarządzania content-driven business

---

## 🏆 TAGLINE

**PearBlog Engine — content, który sprzedaje.**

---

## 📚 NEXT STEPS

1. **Review** - Przejrzyj dokumentację z zespołem
2. **Prototype** - Zbuduj MVP (Article + AI + API)
3. **Test** - Porównaj z obecnym WordPress Plugin
4. **Decide** - Go/No-go na pełną migrację
5. **Execute** - Wdróż zgodnie z Migration Path

---

**Wersja:** 1.0
**Data:** 2026-05-04
**Status:** Strategic Vision & Architecture Blueprint
**Autor:** PearBlog Team
