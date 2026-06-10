# Contributing to Comic Universe

Thank you for your interest in contributing to Comic Universe! This document provides guidelines for reporting issues, requesting features, and submitting code.

## Code of Conduct

Be respectful, inclusive, and professional in all interactions. We are committed to providing a welcoming environment for all contributors.

---

## Reporting Issues

### Before You Report
1. Check existing issues to avoid duplicates
2. Test on latest version
3. Gather environment details (PHP version, OS, etc.)

### Issue Template

```markdown
**Description**
Brief description of the issue

**Steps to Reproduce**
1. Do this
2. Then this
3. Finally this

**Expected Behavior**
What should happen

**Actual Behavior**
What actually happened

**Environment**
- OS: (Windows/Linux/macOS)
- PHP Version: 8.1.0
- Browser: Chrome 120
- Database: MySQL 8.0 / SQLite 3.40

**Error Messages**
```
Copy error logs here
```

**Screenshots**
(if applicable)
```

### Issue Categories

**Bug Report:**
- Title: `[BUG] Brief description`
- Use template above
- Include error logs and reproduction steps

**Security Issue:**
- Do NOT post publicly
- Email: security@your-domain.com
- Include severity level

**Documentation Issue:**
- Title: `[DOCS] Brief description`
- Specify which file/section
- Explain the issue

---

## Feature Requests

### Template

```markdown
**Title:** [FEATURE] Brief description

**Problem Statement**
Describe the problem this solves

**Proposed Solution**
How should this be implemented?

**Alternative Solutions**
Other approaches considered?

**Additional Context**
Examples, use cases, references
```

### Review Criteria

Features are evaluated on:
- Alignment with project goals
- User demand and impact
- Implementation complexity
- Maintainability
- Backwards compatibility

---

## Code Contribution Guidelines

### Development Setup

```bash
# 1. Fork the repository
# (click Fork on GitHub)

# 2. Clone your fork
git clone https://github.com/YOUR-USERNAME/sitecomics2.git
cd sitecomics2

# 3. Add upstream remote
git remote add upstream https://github.com/original-owner/sitecomics2.git

# 4. Create feature branch
git checkout -b feature/your-feature-name

# 5. Make changes
# (see coding standards below)

# 6. Commit changes
git commit -m "feat: Add description of changes"

# 7. Push to your fork
git push origin feature/your-feature-name

# 8. Create Pull Request
# (on GitHub, create PR from your fork to upstream)
```

### Git Workflow

#### Branch Naming

```
feature/description          # New features
fix/description              # Bug fixes
docs/description             # Documentation updates
refactor/description         # Code refactoring
test/description             # Test improvements
security/description         # Security fixes
performance/description      # Performance improvements
chore/description            # Maintenance tasks
```

#### Commit Message Format

```
<type>(<scope>): <subject>

<body>

<footer>
```

**Types:**
- `feat` - New feature
- `fix` - Bug fix
- `docs` - Documentation
- `style` - Code formatting
- `refactor` - Code refactoring
- `test` - Test additions
- `perf` - Performance
- `security` - Security fixes

**Example:**
```
feat(api): Add pagination to comic listing

- Implement limit and offset parameters
- Update GET /api/get.php endpoint
- Add tests for pagination

Fixes #123
```

### Code Style Guide

#### PHP Code Standards

**Naming Conventions:**
```php
// Classes - PascalCase
class UserRepository { }

// Functions - camelCase
function getUserById($id) { }

// Constants - UPPER_SNAKE_CASE
const DATABASE_TIMEOUT = 5000;

// Variables - snake_case or camelCase
$user_data = [];
$userData = [];  // Also acceptable
```

**File Organization:**
```php
<?php
// 1. Namespace and imports
namespace App\Controllers;

use PDO;
use App\Models\User;

// 2. Class definition
class UserController {
    // 3. Properties
    private $db;
    
    // 4. Constructor
    public function __construct($db) {
        $this->db = $db;
    }
    
    // 5. Public methods
    public function getUser($id) {
        // Implementation
    }
    
    // 6. Protected methods
    protected function validateInput($data) {
        // Implementation
    }
    
    // 7. Private methods
    private function parseResponse($data) {
        // Implementation
    }
}
```

**Code Formatting:**
```php
// Use 4-space indentation (no tabs)
if ($condition) {
    echo "Indented with 4 spaces";
}

// Opening braces on same line
function test() {
    // body
}

// Spaces around operators
$result = $a + $b;

// No spaces inside parentheses
function test($a, $b) { }

// Blank line between methods
public function method1() {
    // body
}

public function method2() {
    // body
}
```

**Security Best Practices:**
```php
// DO: Use prepared statements
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);

// DON'T: Concatenate user input
$result = $pdo->query("SELECT * FROM users WHERE id = $id");

// DO: Escape output
echo htmlspecialchars($user_input, ENT_QUOTES, 'UTF-8');

// DON'T: Output without escaping
echo $user_input;

// DO: Hash passwords
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);

// DON'T: Store plain text
$plain = $_POST['password'];
```

**Error Handling:**
```php
// DO: Handle exceptions
try {
    $result = performDatabaseQuery();
} catch (Exception $e) {
    error_log("Query failed: " . $e->getMessage());
    throw new RuntimeException("Database error");
}

// DON'T: Suppress errors
@$result = performDatabaseQuery();
```

**Comments and Documentation:**
```php
/**
 * Retrieve user by ID
 * 
 * @param int $id User ID
 * @return User|null User object or null if not found
 * @throws DatabaseException If database query fails
 */
public function getUserById($id) {
    // Validate input
    if (!is_int($id) || $id <= 0) {
        throw new InvalidArgumentException("Invalid user ID");
    }
    
    // Query database
    $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    
    return $stmt->fetchObject('User');
}
```

### Testing Requirements

All code changes must include tests:

```php
// tests/UserRepositoryTest.php
<?php
use PHPUnit\Framework\TestCase;

class UserRepositoryTest extends TestCase {
    private $repository;
    
    protected function setUp(): void {
        $this->repository = new UserRepository($this->mockDb);
    }
    
    public function testGetUserById(): void {
        $user = $this->repository->getUserById(1);
        $this->assertEquals('John', $user->name);
    }
    
    public function testGetUserNotFound(): void {
        $user = $this->repository->getUserById(99999);
        $this->assertNull($user);
    }
}
```

**Run Tests:**
```bash
php vendor/bin/phpunit
```

**Minimum Coverage:**
- 80% code coverage required
- All public methods must have tests
- Edge cases must be tested
- Error paths must be tested

### Performance Considerations

**Do:**
- Use indexes on frequently queried fields
- Implement pagination for large result sets
- Cache database results appropriately
- Use batch operations when possible
- Minimize database queries

**Don't:**
- Perform N+1 queries in loops
- Load entire tables into memory
- Disable indexes for performance
- Cache sensitive data
- Use string concatenation in loops

### Documentation

**Update these files if applicable:**
- `README.md` - Major features
- `KNOWLEDGE_BASE.md` - Architecture changes
- `CHANGELOG.md` - Feature description
- Inline code comments - Complex logic
- Docblock comments - Public APIs

---

## Pull Request Process

### Before Submitting

1. **Update CHANGELOG.md:**
   ```markdown
   - [Feature/Fix] Brief description of your changes
   ```

2. **Test thoroughly:**
   ```bash
   php tests/functional-test.php
   php vendor/bin/phpunit
   ```

3. **Check code style:**
   ```bash
   # Manual review of your changes
   git diff main
   ```

4. **Verify no conflicts:**
   ```bash
   git fetch upstream
   git rebase upstream/main
   ```

### PR Template

```markdown
## Description
Brief description of changes

## Type of Change
- [ ] Bug fix
- [ ] New feature
- [ ] Breaking change
- [ ] Documentation update

## Related Issues
Fixes #123

## Changes Made
- Change 1
- Change 2
- Change 3

## Testing
How was this tested?

## Screenshots (if UI changes)
Before/after screenshots

## Checklist
- [ ] Code follows style guide
- [ ] Tests added/updated
- [ ] Documentation updated
- [ ] No new warnings generated
- [ ] Tested locally
```

### Review Process

1. **Automated checks:**
   - Code style validation
   - Test suite execution
   - Code coverage analysis

2. **Manual review:**
   - Code quality assessment
   - Security review
   - Performance impact
   - Documentation check

3. **Approval:**
   - At least 1 approval required
   - No unresolved comments
   - CI/CD passing

4. **Merge:**
   - Squash commits if needed
   - Delete feature branch
   - Update related issues

---

## Development Environment Setup

### Required Tools

```bash
# PHP 8.1+
php --version

# Composer
composer --version

# Git
git --version

# (Optional) Docker
docker --version
```

### Installation

```bash
# Clone repository
git clone https://github.com/your-username/sitecomics2.git
cd sitecomics2

# Install dependencies
composer install

# Setup environment
cp .env.example .env
echo 'ENVIRONMENT=development' >> .env
echo 'DEBUG_MODE=true' >> .env

# Initialize database
php setup-db.php

# Start server
php -S localhost:8000
```

### IDE Setup (VS Code)

**Extensions:**
- PHP Intelephense
- PHP CodeSniffer
- Prettier
- GitLens

**.vscode/settings.json:**
```json
{
    "php.validate.executablePath": "/usr/bin/php",
    "editor.formatOnSave": true,
    "editor.defaultFormatter": "esbenp.prettier-vscode",
    "[php]": {
        "editor.defaultFormatter": "intelephense",
        "editor.formatOnSave": true
    }
}
```

---

## Security Guidelines

### Reporting Security Issues

**DO NOT** open public GitHub issues for security vulnerabilities.

Instead:
1. Email: security@your-domain.com
2. Include:
   - Description of vulnerability
   - Steps to reproduce
   - Potential impact
   - Suggested fix (optional)

**We will:**
- Acknowledge receipt within 48 hours
- Investigate thoroughly
- Develop and test fix
- Release patch
- Credit discoverer (if desired)

### Secure Coding Practices

See [SECURITY.md](SECURITY.md) for detailed security guidelines.

**Key Points:**
- Use prepared statements
- Validate all input
- Escape all output
- Hash passwords with bcrypt
- Use HTTPS in production
- Keep dependencies updated
- Enable security headers
- Log security events

---

## Recognized Contributors

Contributors are recognized in:
1. [CONTRIBUTORS.md](CONTRIBUTORS.md) file
2. GitHub repository
3. Release notes

To be included:
- Make a contribution (code, docs, etc.)
- Mention in your PR if you want attribution

---

## Questions?

- Check [FAQ section](KNOWLEDGE_BASE.md#13-frequently-asked-questions)
- Search existing issues
- Ask in GitHub discussions
- Email: help@your-domain.com

---

## License

By contributing, you agree that your contributions will be licensed under the same license as the project (see LICENSE file).

---

**Thank you for contributing!**

Last Updated: 2024-01-15  
Maintainer: [Your Name]
