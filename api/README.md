# Comic Universe REST API

## Endpoints

### 1. Получить список комиксов
**URL:** `/api/read.php`  
**Метод:** GET  
**Параметры:**
- `limit` (опционально) - количество комиксов (по умолчанию 10, максимум 100)

**Примеры:**
```
http://localhost:8000/api/read.php
http://localhost:8000/api/read.php?limit=5
```

**Ответ (200 OK):**
```json
{
  "status": "success",
  "count": 5,
  "data": [
    {
      "id": 1,
      "title": "Мстители",
      "price": 4500,
      "image": "https://...",
      "category": "Marvel"
    }
  ]
}
```

---

### 2. Получить комикс по ID
**URL:** `/api/get.php`  
**Метод:** GET  
**Параметры:**
- `id` (обязательно) - ID комикса

**Примеры:**
```
http://localhost:8000/api/get.php?id=1
http://localhost:8000/api/get.php?id=5
```

**Ответ (200 OK):**
```json
{
  "status": "success",
  "data": {
    "id": 1,
    "title": "Мстители",
    "price": 4500,
    "image": "https://...",
    "category": "Marvel"
  }
}
```

**Ошибки:**
- 400: Параметр `id` не передан
- 404: Комикс не найден

---

### 3. Получить список категорий
**URL:** `/api/categories.php`  
**Метод:** GET  

**Пример:**
```
http://localhost:8000/api/categories.php
```

**Ответ (200 OK):**
```json
{
  "status": "success",
  "count": 8,
  "data": [
    {"id": 1, "name": "Marvel"},
    {"id": 2, "name": "Batman Comics"},
    {"id": 3, "name": "Spider man"}
  ]
}
```

---

### 4. Создание нового комикса
**URL:** `/api/create.php`  
**Метод:** POST  
**Тело запроса (JSON):**
```json
{
  "title": "Новый комикс",
  "price": 5000,
  "image": "image.png",
  "category_id": 1
}
```

**Ответ (201 Created):**
```json
{
  "status": "success",
  "message": "Comic created successfully",
  "data": {
    "id": 13,
    "title": "Новый комикс",
    "price": 5000,
    "image": "image.png",
    "category": "Marvel"
  }
}
```

**Ошибки:**
- 400: Отсутствуют обязательные поля или неверные значения
- 405: Используется неправильный метод запроса

---

### 5. Обновление комикса
**URL:** `/api/update.php`  
**Метод:** PUT  
**Тело запроса (JSON):**
```json
{
  "id": 1,
  "title": "Обновленное название",
  "price": 6000,
  "image": "new_image.png",
  "category_id": 2
}
```

**Ответ (200 OK):**
```json
{
  "status": "success",
  "message": "Comic updated successfully",
  "data": {
    "id": 1,
    "title": "Обновленное название",
    "price": 6000,
    "image": "new_image.png",
    "category": "Batman Comics"
  }
}
```

**Ошибки:**
- 400: Отсутствует ID или неверные значения полей
- 404: Комикс с таким ID не найден
- 405: Используется неправильный метод запроса

---

### 6. Удаление комикса
**URL:** `/api/delete.php`  
**Метод:** DELETE  
**Тело запроса (JSON):**
```json
{
  "id": 1
}
```

**Ответ (200 OK):**
```json
{
  "status": "success",
  "message": "Comic with ID 1 deleted successfully"
}
```

**Ошибки:**
- 400: Поле "id" отсутствует
- 404: Комикс с таким ID не найден
- 405: Используется неправильный метод запроса

---

## Таблица HTTP-кодов по endpoint'ам

| Endpoint | Метод | Успех | Ошибки |
|----------|-------|-------|--------|
| `/api/read.php` | GET | 200 | 500 |
| `/api/get.php` | GET | 200 | 400, 404, 500 |
| `/api/categories.php` | GET | 200 | 500 |
| `/api/create.php` | POST | 201 | 400, 405, 500 |
| `/api/update.php` | PUT | 200 | 400, 404, 405, 500 |
| `/api/delete.php` | DELETE | 200 | 400, 404, 405, 500 |

---

## HTTP-коды

- **200 OK** — успешное выполнение запроса
- **201 Created** — ресурс успешно создан
- **400 Bad Request** — ошибка валидации, отсутствуют обязательные поля
- **404 Not Found** — ресурс не найден
- **405 Method Not Allowed** — используется неправильный HTTP метод
- **500 Internal Server Error** — ошибка базы данных

---

## Особенности реализации

- ✅ Все запросы используют **PDO prepared statements** для защиты от SQL-инъекций
- ✅ Флаг **JSON_UNESCAPED_UNICODE** для корректного отображения русского текста
- ✅ Заголовок **Content-Type: application/json; charset=utf-8**
- ✅ Проверка метода запроса: **405 Method Not Allowed** для чужих методов
- ✅ Валидация всех полей на сервере
- ✅ Проверка существования категории перед сохранением
- ✅ Объединение таблиц через **JOIN** для получения полной информации
- ✅ Идемпотентность DELETE — повторный запрос возвращает 404
- ✅ Правильные HTTP статусы для каждого сценария

## Тестирование

Можно протестировать endpoints:
1. В браузере - просто откройте URL (GET запросы)
2. В **Postman** - создайте запрос нужного метода и типа
3. Через **cURL**:
   ```bash
   # GET запросы
   curl http://localhost:8000/api/read.php?limit=5
   curl http://localhost:8000/api/get.php?id=1
   
   # POST - создание
   curl -X POST http://localhost:8000/api/create.php \
     -H "Content-Type: application/json" \
     -d '{"title":"Новый","price":5000,"image":"img.png","category_id":1}'
   
   # PUT - обновление
   curl -X PUT http://localhost:8000/api/update.php \
     -H "Content-Type: application/json" \
     -d '{"id":1,"title":"Обновлено"}'
   
   # DELETE - удаление
   curl -X DELETE http://localhost:8000/api/delete.php \
     -H "Content-Type: application/json" \
     -d '{"id":1}'
   ```
