-- Claims Management tables (SQL Server)
-- Run this in SSMS or Laragon terminal if: php artisan migrate fails
-- Database: your ESS database (e.g. FTT_HRMS_ESS)

IF OBJECT_ID(N'claim_workflow_logs', N'U') IS NOT NULL DROP TABLE claim_workflow_logs;
IF OBJECT_ID(N'claim_attachments', N'U') IS NOT NULL DROP TABLE claim_attachments;
IF OBJECT_ID(N'claims', N'U') IS NOT NULL DROP TABLE claims;
IF OBJECT_ID(N'claim_workflows', N'U') IS NOT NULL DROP TABLE claim_workflows;
IF OBJECT_ID(N'company_claim_configs', N'U') IS NOT NULL DROP TABLE company_claim_configs;
GO

CREATE TABLE company_claim_configs (
    id BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    company_id BIGINT NOT NULL,
    expense_enabled BIT NOT NULL DEFAULT 1,
    conveyance_enabled BIT NOT NULL DEFAULT 1,
    travel_enabled BIT NOT NULL DEFAULT 1,
    created_by BIGINT NULL,
    created_at DATETIME2 NULL,
    updated_at DATETIME2 NULL,
    CONSTRAINT UQ_company_claim_configs_company_id UNIQUE (company_id)
);
CREATE INDEX IX_company_claim_configs_company_id ON company_claim_configs (company_id);
GO

CREATE TABLE claim_workflows (
    id BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    company_id BIGINT NOT NULL,
    route_name NVARCHAR(64) NOT NULL,
    claim_type NVARCHAR(32) NOT NULL,
    serial_number TINYINT NOT NULL,
    role_type NVARCHAR(32) NOT NULL,
    approver_user_id BIGINT NULL,
    department_id BIGINT NULL,
    status NVARCHAR(20) NOT NULL DEFAULT 'active',
    created_by BIGINT NULL,
    created_at DATETIME2 NULL,
    updated_at DATETIME2 NULL,
    CONSTRAINT claim_workflows_level_unique UNIQUE (company_id, claim_type, route_name, serial_number)
);
CREATE INDEX IX_claim_workflows_company ON claim_workflows (company_id, claim_type, route_name);
GO

CREATE TABLE claims (
    id BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    company_id BIGINT NOT NULL,
    employee_id BIGINT NOT NULL,
    claim_type NVARCHAR(32) NOT NULL,
    claim_no NVARCHAR(64) NOT NULL,
    claim_date DATE NOT NULL,
    amount DECIMAL(15,2) NOT NULL DEFAULT 0,
    status NVARCHAR(20) NOT NULL DEFAULT 'draft',
    pending_user_id BIGINT NULL,
    current_workflow_level TINYINT NOT NULL DEFAULT 1,
    route_name NVARCHAR(64) NULL,
    pending_role_type NVARCHAR(32) NULL,
    narration NVARCHAR(MAX) NULL,
    employee_remark NVARCHAR(MAX) NULL,
    manager_remark NVARCHAR(MAX) NULL,
    final_remarks NVARCHAR(MAX) NULL,
    passed_amount DECIMAL(15,2) NULL,
    bill_no NVARCHAR(128) NULL,
    bill_date DATE NULL,
    details NVARCHAR(MAX) NULL,
    pending_since DATETIME2 NULL,
    submitted_at DATETIME2 NULL,
    created_by BIGINT NULL,
    created_at DATETIME2 NULL,
    updated_at DATETIME2 NULL
);
CREATE INDEX IX_claims_company_employee ON claims (company_id, employee_id, claim_date);
CREATE INDEX IX_claims_company_status ON claims (company_id, status, claim_type);
CREATE INDEX IX_claims_claim_no ON claims (claim_no);
CREATE INDEX IX_claims_pending_user ON claims (pending_user_id);
GO

CREATE TABLE claim_attachments (
    id BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    claim_id BIGINT NOT NULL,
    file_name NVARCHAR(255) NOT NULL,
    file_path NVARCHAR(500) NOT NULL,
    created_by BIGINT NULL,
    created_at DATETIME2 NULL,
    updated_at DATETIME2 NULL,
    CONSTRAINT FK_claim_attachments_claim FOREIGN KEY (claim_id) REFERENCES claims(id) ON DELETE CASCADE
);
CREATE INDEX IX_claim_attachments_claim_id ON claim_attachments (claim_id);
GO

CREATE TABLE claim_workflow_logs (
    id BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    claim_id BIGINT NOT NULL,
    workflow_level TINYINT NULL,
    action NVARCHAR(32) NOT NULL,
    remarks NVARCHAR(MAX) NULL,
    action_by BIGINT NULL,
    from_pending_role NVARCHAR(32) NULL,
    to_pending_role NVARCHAR(32) NULL,
    from_pending_user_id BIGINT NULL,
    to_pending_user_id BIGINT NULL,
    action_date DATETIME2 NOT NULL,
    created_by BIGINT NULL,
    created_at DATETIME2 NULL,
    updated_at DATETIME2 NULL,
    CONSTRAINT FK_claim_workflow_logs_claim FOREIGN KEY (claim_id) REFERENCES claims(id) ON DELETE CASCADE
);
CREATE INDEX IX_claim_workflow_logs_claim_id ON claim_workflow_logs (claim_id);
GO

-- Register migration (optional — so artisan does not try to re-run)
IF NOT EXISTS (SELECT 1 FROM migrations WHERE migration = N'2026_05_21_140000_create_claims_management_tables')
INSERT INTO migrations (migration, batch)
VALUES (N'2026_05_21_140000_create_claims_management_tables', (SELECT ISNULL(MAX(batch), 0) + 1 FROM migrations));
GO
