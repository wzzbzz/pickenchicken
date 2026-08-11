# Product Requirements Document (PRD)

## Product Name
EntiLynx CDL

## Author
Tim Clayton

## 1. Purpose & Overview
The EntiLynx CDL Portal includes is a web-based application with several components:
- **Data Marketplace** A data mesh inspired marketplace for browsing data product catalogs, publishing, discovering and
consuming data products
- **Data Steward Console** for policy management, monitoring usage and managing data quality issues
- **Data Quality Console** for defining data quality rules and monitoring data quality using interactive dashboards that show data quality trends
- **Admin Console** for managing security such as users, roles and permissions, configuring
Connection-as-a-Service (CaaS) connections to Veeva CTMS and Rave eDC systems, monitoring system health, performance and audit logs.

The product includes:
- **Frontend**: React
- **Backend API**: Express 5
- **Database**: PostgreSQL

This PRD is structured to be easily consumed by AI development tools such as Cursor, GitHub Copilot, or internal LLM-based agents.

## 2. Goals & Success Metrics

### Business Goals
- Business teams discover, publish and subscribe to data products without IT tickets.
- Data stewards can define data access and usage policies, monitor usage and consumption of data products, and can manage issue logs for each data product in a Kanban inspired backlog and board
- Data quality measurably higher over time with positive quality trend transparently visible in Data Quality Dashboard
- IT admins can easily manage user access, roles and permissions, self-service configuration of CTMS and eDC connections, proactively
monitor system health, performance and review audit logs

## 3. User Personas

### Persona 1: Data Consumer
- Use a catalog browser to browse, search, filter, discover, subscribe to, and consume data products
- Can review details and attributes of individual data products including name, product type, description, data source, 
domain, owner, intended audience, provisioning steps, region and country, quality rating, and data structure

### Persona 3: Data Steward
- Can define policies for access management
- Can monitoring usage of data products
- Can define data quality rules and frequency of running data quality checks
- Can review quarantined data that has failed data quality checks
- Can review data quality trend over time based on data quality rules and metrics generated from data quality check runs
- Can manage data quality issues reported for data products in a kanban style backlog and board 
- Can view audit logs for changes made to data products and data consumer subscriptions, and data access logs

### Persona 2: Data Owner
- Can do everythng a data consumer and data steward can do
- Can publish data products and specify attributes including name, product type, description, data source, 
domain, owner, intended audience, provisioning steps, quality rating, region and country, and data structure

### Persona 4: IT Admin
- Can manage users, roles and permissions
- Can configure CaaS connections to CTMS and eDC systems
- Can monitor system health and performance
- Can review audit logs of any system configuration changes

## 4. User Stories

- As a User, any time I save data in the system, I need the system to audit the date and time, user making the change, 
and capture a reason for the change in order to meet the requirements of FDA 21 CFR Part 11 audit trails
- As an IT Admin, I can add, edit and remove users from the system including full name, title, and email address
- As an IT Admin, I can add, edit and remove roles including role name, description
- As an IT Admin, I can assign users to roles
- As in IT Admin, I can assign system permissions to roles
- As an IT Admin, I can configure CaaS connections for Veeva CTMS including API key, endpoint and study name
- As an IT Admin, I can configure CaaS connections for Rave eDC including API key, endpoint and study name
- As an IT Admin, I can monitor system health and performance by reviewing the status of jobs for data pipelines,
data quality checks, and general system errors
- As an IT Admin, I can review audit logs of any system configuration changes, including changes to users, roles, 
user role assigments, role permission assignments, CaaS connection configuration changes
- As a Data Consumer, I can browse the catalog and view available data products and their attributes including
name, description, domain, owner, intended audience and quality rating
- As a Data Consumer, I can filter the catalog results by product type, data source, domain, region and country
- As a Data Consumer, I can use a text search box to find data products
- As a Data Consumer, I can select an available data product and view its details including name, product type, 
description, data source, domain, owner, intended audience, provisioning steps, quality rating, region and country,
data structure, and associated data quality metrics from associated data quality check runs
- As a Data Steward, I can define policies for data products including issue handling procedures, approved data uses,
restricted data uses, sharing guidelines, data privacy and compliance rules, retention schedules, archiving requirements,
data deletion rules, cataloging requirements, ethics and responsible use
- As a Data Steward, I can monitor the Data Consumer usage of published data products
- As a Data Steward, I can define data quality checks and specify the frequency of running data quality checks
- As a Data Steward, I can review quarantined data that has failed data quality checks
- As a Data Steward, I can review data quality trend over time based on data quality rules and metrics generated 
from data quality check runs
- As a Data Steward, I can manage data quality issues reported for data products in a kanban style backlog and board
- As a Data Steward, I can view audit logs for changes made to data products and data consumer subscriptions and data access logs
- As a Data Owner, I can publish data products and specify attributes including name, product type, description, data source, 
domain, owner, intended audience, provisioning steps, quality rating, region and country, and data structure

## 5. Stack

- **Monorepo tool**: pnpm workspaces
- **Node.js version**: 24
- **Package manager**: pnpm
- **TypeScript version**: 5.9
- **API framework**: Express 5
- **Database**: PostgreSQL + Drizzle ORM
- **Validation**: Zod (`zod/v4`), `drizzle-zod`
- **API codegen**: Orval (from OpenAPI spec)
- **Build**: esbuild (CJS bundle)
- **Frontend**: React + Vite + Tailwind CSS + Shadcn UI + Recharts

## 6. Applications

### EntiLynx CDL (`artifacts/cdl-console`)
A full-stack application to manage the CDL (clinical data lake)

#### Pages
- **Publish Data Product**: Allows the Data Owner to publish data products and specify attributes including name, 
product type, description, data source, domain, owner, intended audience, provisioning steps, quality rating, region and country, 
and data structure
- **Data Product Policies**: Allows the Data Steward to define policies for data products including issue handling procedures, 
approved data uses, restricted data uses, sharing guidelines, data privacy and compliance rules, retention schedules, 
archiving requirements, data deletion rules, cataloging requirements, ethics and responsible use
- **Data Product Usage**: Allows the Data Steward to monitor the Data Consumer usage of published data products
- **Data Product Usage**: Allows the Data Steward to monitor the Data Consumer usage of published data products
- **Data Quality Rules**: Allows the Data Stweard to configure data quality checks and specify the frequency of running 
data quality checks 
- **Quarantined Data**: Allows the Data Steward to review quarantined data that has failed data quality checks
- **Data Quality Dashboard**: Allows the Data Steward to review data quality trend over time based on data quality rules 
and metrics generated from data quality check runs, including trend charts + data qualitgy runs table
- **Data Quality Issues**: Allows the Data Steward to manage data quality issues reported for data products in a 
kanban style backlog and board
- **Data Product History**: Allows the Data Steward to view audit logs for changes made to data products and data consumer 
subscriptions and data access logs
- **Catalog Browser**: Allows the Data Consumer to browse, search, filter, discover, subscribe to, and consume data products
- **Data Product Details**: Allows the Data Consumer to view a data product's details including name, product type, 
description, data source, domain, owner, intended audience, provisioning steps, quality rating, region and country,
data structure, and associated data quality metrics from associated data quality check runs
- **CaaS Connections**: Allows the IT Admin to review a list of CaaS connections and add, delete, or update connections
and review the RAG status of the connection
- **Add CaaS Connection**: Allows the IT Admin to configure CaaS connectin for Veeva CTMS and Medidata Rave by specifying 
API key, endpoint and study name
- **System Health**: Allows the IT Admin to monitor system health and performance by reviewing the status of jobs for data pipelines,
data quality checks, and general system errors
- **Manage Users**: Allows the IT Admin to add, edit and remove users from the system and update user's full name, title, and email address,
and role memberships
- **Manage Roles**: Allows the IT Admin to add, edit and remove roles from the system and update the role's  name, description,
and permissions
- **System History**: Allows the IT Admin to monitor system health and performance by reviewing the status of jobs for data pipelines,
data quality checks, and general system errors
- **Preview**: served at `/`

#### Look and Feel
- **Color Pallette**: #004355, #88C265 and #FFFFFF
- **Fonts**: Sans Serif througout

### API Server (`artifacts/cdl-api-server`)
- Express 5 backend serving all REST API endpoints
- **Preview**: served at `/api`

## 7. Structure

```text
artifacts-monorepo/
├── artifacts/
│   ├── cdl-api-server/     # Express API server
│   └── cdl-console/        # React + Vite frontend (EntiLynx CDL Console)
├── lib/
│   ├── api-spec/           # OpenAPI spec + Orval codegen config
│   ├── api-client-react/   # Generated React Query hooks
│   ├── api-zod/            # Generated Zod schemas from OpenAPI
│   ├── db/                 # Drizzle ORM schema + DB connection
├── scripts/                # Utility scripts
├── pnpm-workspace.yaml
├── tsconfig.base.json
├── tsconfig.json
└── package.json
```

## 8. Database Schema

### data_products table

Data product information

```sql
id UUID PRIMARY KEY
name TEXT UNIQUE NOT NULL 
product_type TEXT NOT NULL CHECK (product_type IN ('Data Product','Report'))
description TEXT NOT NULL 
data_source UUID NOT NULL REFERENCES caas_connections(id)
domain TEXT NOT NULL CHECK (domain IN ('Clinical Operations','Clinical Programs','Site Monitoring'))
data_owner_user_id TEXT NOT NULL REFERENCES users(id)
intended_audience TEXT NOT NULL 
provisioning_steps TEXT NOT NULL CHECK (provisioning_steps IN ('Owner Approval','Departmental'))
quality_rating NUMBER NOT NULL DEFAULT 5 CHECK (quality_rating IN (1,2,3,4,5))
country_and_regions TEXT NOT NULL 
enabled_flag BOOLEAN NOT NULL DEFAULT true
audit_id UUID REFERENCES data_product_audit_history(id)
```

### data_product_usage table

Data product access history

```sql
id UUID PRIMARY KEY
audit_id UUID REFERENCES audit_history(id)
```

### data_product_audit_history table

Audit trail for changes to data products

```sql
id UUID PRIMARY KEY
data_product_id UUID REFERENCES data_products(id)
name TEXT UNIQUE NOT NULL 
product_type TEXT NOT NULL CHECK (product_type IN ('Data Product','Report'))
description TEXT NOT NULL 
data_source UUID NOT NULL REFERENCES caas_connections(id)
domain TEXT NOT NULL CHECK (domain IN ('Clinical Operations','Clinical Programs','Site Monitoring'))
data_owner_user_id TEXT NOT NULL REFERENCES users(id)
intended_audience TEXT NOT NULL 
provisioning_steps TEXT NOT NULL CHECK (provisioning_steps IN ('Owner Approval','Departmental'))
quality_rating NUMBER NOT NULL DEFAULT 5 CHECK (quality_rating IN (1,2,3,4,5))
country_and_regions TEXT NOT NULL 
enabled_flag BOOLEAN NOT NULL
audit_id UUID REFERENCES audit_history(id)
```

### data_quality_rules table

Data quality rule configurations

```sql
id UUID PRIMARY KEY
name TEXT UNIQUE NOT NULL 
description TEXT NOT NULL 
severity TEXT NOT NULL CHECK (severity IN ('critical','high','medium','low','info'))
category TEXT NOT NULL CHECK (category IN ('required','duplicate','orphan','constraint'))
subcategory TEXT NOT NULL CHECK (subcategory IN ('study','site','subject','form','item','query'))
sql_query TEXT NOT NULL
schedule TEXT NOT NULL CHECK (schedule IN ('manual','weekly','daily','hourly'))
enabled_flag BOOLEAN NOT NULL DEFAULT true
audit_id UUID REFERENCES data_quality_rules_audit_history(id)
```

### data_quality_rules_audit_history table

Audit trail for changes to data quality rules

```sql
id UUID PRIMARY KEY
data_quality_rule_id UUID REFERENCES data_quality_rules(id)
name TEXT UNIQUE NOT NULL 
description TEXT NOT NULL 
severity TEXT NOT NULL CHECK (severity IN ('critical','high','medium','low','info'))
category TEXT NOT NULL CHECK (category IN ('required','duplicate','orphan','constraint'))
subcategory TEXT NOT NULL CHECK (subcategory IN ('study','site','subject','form','item','query'))
sql_query TEXT NOT NULL
schedule TEXT NOT NULL CHECK (schedule IN ('manual','weekly','daily','hourly'))
enabled_flag BOOLEAN NOT NULL
audit_id UUID REFERENCES audit_history(id)
```

### data_quality_runs table

Data quality run history and results for each rule

```sql
id UUID PRIMARY KEY
data_quality_rule_id UUID REFERENCES data_quality_rules(id)
status TEXT NOT NULL CHECK (status IN ('pass','fail','system error'))
record_count NUMBER NOT NULL
audit_id UUID REFERENCES audit_history(id)
```

### data_quality_issues table

Data quality issue log

```sql
id UUID PRIMARY KEY
title TEXT NOT NULL 
description TEXT NULL 
severity TEXT NOT NULL CHECK (severity IN ('critical','high','medium','low'))
audit_id UUID REFERENCES audit_history(id)
```

### caas_connections table

CaaS connection information

```sql
id UUID PRIMARY KEY
source_system TEXT NOT NULL CHECK (source_system IN ('Veeva CTMS','Medidata CTMS','Oracle CTMS','Veeva Vault eDC','Medidata Rave eDC','Oracle InForm'))
api_key TEXT NOT NULL 
study_name TEXT NOT NULL 
enabled_flag BOOLEAN NOT NULL DEFAULT true
audit_id UUID REFERENCES caas_connection_audit_history(id)
```

### caas_connection_audit_history table

Audit trail for changes to CaaS connections

```sql
id UUID PRIMARY KEY
caas_connection_id UUID REFERENCES caas_connections(id)
source_system TEXT NOT NULL CHECK (source_system IN ('Veeva CTMS','Medidata CTMS','Oracle CTMS','Veeva Vault eDC','Medidata Rave eDC','Oracle InForm'))
api_key TEXT NOT NULL 
study_name TEXT NOT NULL 
enabled_flag BOOLEAN NOT NULL
audit_id UUID REFERENCES audit_history(id)
```

### caas_job_history table

History of CaaS connection jobs

```sql
id UUID PRIMARY KEY
caas_connection_id UUID REFERENCES caas_connections(id)
status TEXT NOT NULL CHECK (status IN ('green','yellow','red'))
record_count NUMBER NOT NULL
log_message TEXT NULL
audit_id UUID REFERENCES audit_history(id)
```

### users table

System user information

```sql
id UUID PRIMARY KEY
full_name TEXT NOT NULL
title TEXT NOT NULL
email EMAIL NOT NULL
enabled_flag BOOLEAN NOT NULL DEFAULT true
audit_id UUID REFERENCES users_audit_history(id)
```

### users_audit_history table

Audit trail for changes to users

```sql
id UUID PRIMARY KEY
user_id UUID REFERENCES users(id)
full_name TEXT NOT NULL
title TEXT NOT NULL
email EMAIL NOT NULL
enabled_flag BOOLEAN NOT NULL
audit_id UUID REFERENCES audit_history(id)
```

### roles table

System role information

```sql
id UUID PRIMARY KEY
role_name TEXT UNIQUE NOT NULL
description TEXT NULL
enabled_flag BOOLEAN NOT NULL DEFAULT true
audit_id UUID REFERENCES users_audit_history(id)
```

### roles_audit_history table

Audit trail for changes to roles

```sql
id UUID PRIMARY KEY
role_id UUID REFERENCES roles(id)
role_name TEXT UNIQUE NOT NULL
description TEXT NULL
enabled_flag BOOLEAN NOT NULL
audit_id UUID REFERENCES audit_history(id)
```

### role_membership table

User role membership

```sql
id UUID PRIMARY KEY
user_id UUID REFERENCES users(id)
role_id UUID REFERENCES roles(id)
study_name TEXT NOT NULL 
audit_id UUID REFERENCES role_memberships_audit_history(id)
```

### role_membership_audit_history table

Audit trail for changes to roles

```sql
id UUID PRIMARY KEY
user_id UUID REFERENCES users(id)
role_id UUID REFERENCES roles(id)
audit_id UUID REFERENCES audit_history(id)
```

### audit_history table

Audit history for all data changes

```sql
id UUID PRIMARY KEY
user_id UUID REFERENCES users(id)
created_at TIMESTAMP DEFAULT NOW()
audit_type CHECK (audit_type IN ('created','updated','removed'))
reason_for_change TEXT NULL
```

## 9. Seeded Data for Development and Testing

- **data_products**: create 10 data products with across domains
- **data_product_usage**: create 5 access audit entries for each data product
- **data_product_audit_history**: create at least one entry for the creation of the data product and some examples of updates
- **data_quality_rules**: create 25 data quality rules with varying severity, categories, subcategories
- **data_quality_rules_audit_history**: create at least one entry for the creation of the data quality rules and some examples of updates
- **data_quality_runs**: create 30 days of run history for trend chart visualization.
- **data_quality_issues**: create 50 data issues with varying severity
- **caas_connections**: create CaaS connections for 10 study names to both Veeva CTMS and Medidata Rave source systems 
- **caas_connection_audit_history**: create at least one entry for the creation of each caas connection 
- **caas_job_history**: create 30 days of run history
- **users**: create 50 sample users with approximately 80% data consumers, 15% data stewards, 5% data owners, plus 2 IT admins
- **users_audit_history**: create at least one entry for the creation of each user
- **roles**: create one role for each user persona
- **roles_audit_history**: create at least one entry for the creation of each role
- **role_membership**: Add each user to one role for 3 different study_names
- **role_membership_audit_history**: create at least one entry for the creation of each role_membership
- **audit_history**: create as necessary to link to the otehr *_audit_history tables
