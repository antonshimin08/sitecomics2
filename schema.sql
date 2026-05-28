-- Database schema for the Comic Store project
-- This script is compatible with SQLite and can also work for MySQL with minor changes.

CREATE TABLE IF NOT EXISTS categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    email TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    role TEXT DEFAULT 'user',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS comics (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    price INTEGER NOT NULL,
    image TEXT NOT NULL,
    category_id INTEGER NOT NULL,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

INSERT OR IGNORE INTO categories (id, name) VALUES
    (1, 'Marvel'),
    (2, 'Batman Comics'),
    (3, 'Spider man'),
    (4, 'Green Lantern'),
    (5, 'DC Comics'),
    (6, 'X-Men'),
    (7, 'Watchmen'),
    (8, 'Deadpool');

INSERT OR IGNORE INTO comics (id, title, price, image, category_id) VALUES
    (1, 'Мстители', 4500, 'https://upload.wikimedia.org/wikipedia/ru/5/5c/The_New_Avengers_-1.jpg', 1),
    (2, 'Бэтмен', 6200, 'https://ii1.unicomics.ru/comics/batman-v1/batman-v1-001/01.jpg', 2),
    (3, 'Человек-Паук', 9500, 'https://upload.wikimedia.org/wikipedia/ru/b/b8/SpiderManComic.jpg', 3),
    (4, 'Зелёный фонарь', 3800, 'https://www.mirf.ru/backend/wp-content/uploads/2017/12/985b080765cd3916b7d4ddb0dfc84dcb.jpg', 4),
    (5, 'Железный человек', 5300, '12333.png', 1),
    (6, 'Черная вдова', 4100, '3334.png', 1),
    (7, 'Супермен', 5900, '55656.png', 5),
    (8, 'Чудо-женщина', 5600, '4444.png', 5),
    (9, 'Флэш', 4200, '5555.png', 5),
    (10, 'Логан', 7200, '66666.jpg', 6),
    (11, 'Хранители', 6700, '7776.png', 7),
    (12, 'Дэдпул', 4800, '77868.png', 8);
