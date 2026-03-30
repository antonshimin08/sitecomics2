<?php

$comics = [
    ["id" => 1, "title" => "Мстители", "price" => 4500, "image" => "https://upload.wikimedia.org/wikipedia/ru/5/5c/The_New_Avengers_-1.jpg", "tag" => "Marvel"],
    ["id" => 2, "title" => "Бэтмен", "price" => 6200, "image" => "https://ii1.unicomics.ru/comics/batman-v1/batman-v1-001/01.jpg", "tag" => "Batman Comics"],
    ["id" => 3, "title" => "Человек-Паук", "price" => 9500, "image" => "https://upload.wikimedia.org/wikipedia/ru/b/b8/SpiderManComic.jpg", "tag" => "Spider man"],
    ["id" => 4, "title" => "Зелёный фонарь", "price" => 3800, "image" => "https://www.mirf.ru/backend/wp-content/uploads/2017/12/985b080765cd3916b7d4ddb0dfc84dcb.jpg", "tag" => "Green Lantern"]
];
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Comic Store KZ | С анимациями</title>
    <link href="https://fonts.googleapis.com/css2?family=Bangers&family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #00afca; --dark: #121212; --text: #fff; --accent: #f8e71c; }
        body { font-family: 'Roboto', sans-serif; background: var(--dark); color: var(--text); margin: 0; padding-bottom: 100px; }
        
        header { background: var(--primary); padding: 30px; text-align: center; border-bottom: 5px solid #000; box-shadow: 0 5px 0 #000; margin-bottom: 40px; }
        h1 { font-family: 'Bangers', cursive; font-size: 3.5rem; margin: 0; text-shadow: 3px 3px 0 #000; }

        .container { max-width: 1100px; margin: 0 auto; display: flex; gap: 30px; padding: 0 20px; }
        
 
        .catalog { flex: 2; display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 25px; }

       
        .card { 
            background: #1e1e1e; 
            border: 3px solid #000; 
            padding: 15px; 
            position: relative;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); 
            cursor: pointer;
        }

       
        .card:hover { 
            transform: translateY(-15px) rotate(2deg); 
            border-color: var(--accent);
            box-shadow: 15px 15px 0 var(--primary); 
            z-index: 10;
        }

        .card img { width: 100%; height: 280px; object-fit: cover; border: 2px solid #000; transition: 0.3s; }
        
        .card:hover img { filter: brightness(1.1); }

        .card h3 { font-family: 'Bangers'; color: var(--accent); margin: 15px 0 5px; }

        
        .cart-panel { flex: 1; background: #252525; border: 4px solid var(--primary); padding: 20px; height: fit-content; position: sticky; top: 20px; box-shadow: 8px 8px 0 #000; }
        .cart-panel h2 { font-family: 'Bangers'; border-bottom: 2px solid var(--primary); margin-top: 0; }
        
        .total { font-size: 1.8rem; font-family: 'Bangers'; color: var(--accent); margin: 20px 0; }
        
        .btn-buy { width: 100%; background: var(--primary); border: 2px solid #000; color: #fff; padding: 10px; cursor: pointer; font-family: 'Bangers'; font-size: 1.2rem; transition: 0.2s; }
        .btn-buy:active { transform: scale(0.95); } 
        
        .btn-clear { background: #444; color: #ccc; border: none; padding: 5px 10px; cursor: pointer; margin-top: 10px; width: 100%; }
        
        .cart-item { display: flex; justify-content: space-between; border-bottom: 1px solid #444; padding: 5px 0; font-size: 0.9rem; }
    </style>
</head>
<body>

<header>
    <h1>COMIC UNIVERSE</h1>
</header>

<div class="container">
    <div class="catalog">
        <?php foreach ($comics as $c): ?>
        <div class="card">
            <img src="<?= $c['image'] ?>" alt="Comic">
            <h3><?= $c['title'] ?></h3>
            <p style="font-weight: bold; font-size: 1.3rem;">
                <?= number_format($c['price'], 0, '.', ' ') ?> ₸
            </p>
            <button class="btn-buy" onclick="addToCart('<?= $c['title'] ?>', <?= $c['price'] ?>)">В КОРЗИНУ</button>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="cart-panel">
        <h2>Мой Заказ 📦</h2>
        <div id="cart-items"><p style="color: #666;">Корзина пуста</p></div>
        <div class="total">Итого: <span id="total-price">0</span> ₸</div>
        <button class="btn-clear" onclick="clearCart()">Очистить всё</button>
    </div>
</div>

<script>
    let cart = [];
    let total = 0;

    function formatMoney(num) { return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, " "); }

    function addToCart(title, price) {
        cart.push({title, price});
        total += price;
        updateUI();
    }

    function clearCart() { cart = []; total = 0; updateUI(); }

    function updateUI() {
        const list = document.getElementById('cart-items');
        const totalEl = document.getElementById('total-price');
        if (cart.length === 0) {
            list.innerHTML = '<p style="color: #666;">Корзина пуста</p>';
        } else {
            list.innerHTML = cart.map(item => `
                <div class="cart-item">
                    <span>${item.title}</span>
                    <span>${formatMoney(item.price)} ₸</span>
                </div>
            `).join('');
        }
        totalEl.innerText = formatMoney(total);
    }
</script>

</body>
</html>