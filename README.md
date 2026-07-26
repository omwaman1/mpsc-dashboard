# MPSC Sarathi & Recruitment API 🚀

High-performance REST API for MPSC Sarathi Question Bank and Recruitment Drives connected directly to TiDB Cloud MySQL.

## API Endpoints (`api.php`)

### 1. Questions API
- `GET /api.php?action=questions&page=1&limit=10`
- `GET /api.php?action=questions&subject_id=2&search=1857`
- `GET /api.php?action=questions&topic_id=65`

### 2. Single Question Details
- `GET /api.php?action=get_question_by_id&question_id=49985`

### 3. Update Question Details
- `POST /api.php?action=update_question` (Body: JSON with questionID, questionName, questionNameE, etc.)

### 4. Subject & Topic Hierarchy Tree
- `GET /api.php?action=subjects`
- `GET /api.php?action=topics&subject_id=6`

### 5. Statistics
- `GET /api.php?action=stats`

### 6. Recruitment Jobs API
- `GET /api.php?action=recruitments`
- `GET /api.php?action=recruitment_detail&id=1`
