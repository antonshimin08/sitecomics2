param([int]$ArticleNumber = 1)

$Token = "perm-YWRtaW4=.NDQtMQ==.1jbURED3bHl0sla8hhTx4gMr963mpm"
$YouTrackUrl = "https://antonsimin06.youtrack.cloud"

$Articles = @(
    @{
        title = "1. Project Overview"
        summary = "Comic Universe project overview"
        content = "Project: Comic Universe - PHP e-commerce platform for comic books"
    },
    @{
        title = "2. Project Architecture"
        summary = "System architecture and design"
        content = "Architecture: Web Browser -> Apache -> PHP -> Database"
    },
    @{
        title = "3. Database Schema"
        summary = "Database structure"
        content = "Database tables: users, comics, categories, orders, order_items"
    },
    @{
        title = "4. API Documentation"
        summary = "REST API endpoints"
        content = "API: GET /api/get.php, POST /api/create.php, PUT /api/update.php, DELETE /api/delete.php"
    },
    @{
        title = "5. Installation & Setup"
        summary = "Installation guide"
        content = "Setup: Clone repo, composer install, configure .env, setup-db.php"
    },
    @{
        title = "6. Security Features"
        summary = "Security implementation"
        content = "Security: bcrypt passwords, PDO prepared statements, HTTPS, security headers"
    },
    @{
        title = "7. Production Deployment"
        summary = "Deployment guide"
        content = "Deployment: 10-step process from pre-deployment to monitoring"
    },
    @{
        title = "8. User Roles & Permissions"
        summary = "User role definitions"
        content = "Roles: Admin, User (Registered), Guest (Anonymous)"
    },
    @{
        title = "9. Monitoring & Logging"
        summary = "Monitoring and logs"
        content = "Logging: 5 levels (DEBUG, INFO, WARNING, ERROR, CRITICAL), daily log files"
    },
    @{
        title = "10. Troubleshooting Guide"
        summary = "Common issues and solutions"
        content = "Troubleshooting: 404 errors, database connection, permissions, extensions, performance"
    }
)

$Article = $Articles[$ArticleNumber - 1]

Write-Host "Creating Article #$ArticleNumber..."
Write-Host "Title: $($Article.title)"
Write-Host ""

$Headers = @{
    "Authorization" = "Bearer $Token"
    "Content-Type" = "application/json"
}

$Body = @{
    title = $Article.title
    summary = $Article.summary
    content = $Article.content
} | ConvertTo-Json

try {
    $Response = Invoke-RestMethod -Uri "$YouTrackUrl/api/articles" `
        -Method Post `
        -Headers $Headers `
        -Body $Body `
        -ErrorAction Stop
    
    Write-Host "SUCCESS!" -ForegroundColor Green
    Write-Host "ID: $($Response.id)" -ForegroundColor Green
}
catch {
    Write-Host "ERROR!" -ForegroundColor Red
    Write-Host $_.Exception.Message -ForegroundColor Red
}
