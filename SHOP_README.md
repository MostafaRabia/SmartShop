# SmartShop - Laravel E-commerce with AI Recommendations

## 🚀 Features

### 1. **Home Page** (`/`)
- Hero section with tagline and gradient background
- Search bar powered by **Alpine.js** (live search with debounce)
- Product grid with 20+ seeded products
- **AI-Powered Recommendations** section:
  - Shows "Recommended for You" based on last 3 viewed products
  - Smart fallback to random products if no history exists
  - Uses price-based similarity algorithm (can be replaced with actual AI API)

### 2. **Product Detail Page** (`/product/{id}`)
- Full product information (image, name, description, price)
- Quantity selector with Alpine.js
- "Add to Cart" button with session-based cart (no database)
- Visual feedback when adding to cart
- **"You Might Also Like"** section with AI-powered recommendations
- Product features list
- Breadcrumb navigation

### 3. **Cart Page** (`/cart`)
- Display all cart items with images
- Quantity controls using Alpine.js (increment/decrement)
- Remove item functionality
- Real-time subtotal calculations
- Order summary with total
- **Checkout button** - simulates payment (no real Stripe)
- Success message after checkout
- Empty cart state with call-to-action

## 🛠️ Tech Stack

- **Laravel 11** - Backend framework
- **Livewire 3** - Full-stack reactive framework
- **Alpine.js** - Lightweight JavaScript framework for interactivity
- **Flux UI** - Laravel UI component library
- **Tailwind CSS** - Utility-first CSS framework
- **SQLite** - Database (can be changed to MySQL/PostgreSQL)

## 📦 Installation

1. Clone the repository
2. Install dependencies:
```bash
composer install
npm install
```

3. Set up environment:
```bash
cp .env.example .env
php artisan key:generate
```

4. Run migrations and seed database:
```bash
php artisan migrate:fresh --seed
```

5. Start development servers:
```bash
php artisan serve --port=8001
npm run dev
```

6. Visit: `http://localhost:8001`

## 🎯 How It Works

### Session-Based Cart
The cart uses Laravel sessions to store items. No database tables needed for cart functionality.

```php
session()->get('cart', []);
session()->put('cart', $cart);
```

### AI Recommendations
The system tracks the last 3 viewed products in session:

```php
session()->get('viewed_products', []);
```

Currently uses a price-based similarity algorithm, but can easily be replaced with:
- **OpenAI API** for GPT-based recommendations
- **Google Gemini API** for AI-powered suggestions
- **Custom ML model** for personalized recommendations

### Alpine.js Integration
- Search bar with live filtering
- Quantity controls
- Cart count updates
- Notification system
- Loading states

## 🔧 Configuration

### Enable Real AI Recommendations

To integrate with OpenAI or Gemini, update the `getAIRecommendations` method in:
- `app/Livewire/HomePage.php`
- `app/Livewire/ProductDetail.php`

Example for OpenAI:
```php
protected function getAIRecommendations($viewedProducts)
{
    $client = OpenAI::client(env('OPENAI_API_KEY'));
    
    $prompt = "Based on these products: " . 
              $viewedProducts->pluck('name')->implode(', ') .
              ". Recommend similar products.";
    
    $response = $client->chat()->create([
        'model' => 'gpt-4',
        'messages' => [
            ['role' => 'user', 'content' => $prompt],
        ],
    ]);
    
    // Process response and return products
}
```

## 📁 Project Structure

```
app/
├── Livewire/
│   ├── HomePage.php          # Home page with search & recommendations
│   ├── ProductDetail.php     # Product detail with add to cart
│   └── CartPage.php          # Shopping cart with checkout
│
resources/views/
├── components/
│   └── layout.blade.php      # Main layout with header/footer
├── livewire/
│   ├── home-page.blade.php
│   ├── product-detail.blade.php
│   └── cart-page.blade.php
│
database/
├── migrations/
│   └── 2025_12_19_122903_create_products_table.php
├── factories/
│   └── ProductFactory.php    # Seeds 20+ products with images
└── seeders/
    └── DatabaseSeeder.php
```

## 🎨 UI Components

All pages use **Flux UI** components:
- `<flux:input>` - Search bar
- `<flux:button>` - All buttons
- `<flux:heading>` - Headings
- `<flux:badge>` - AI recommendation badges
- `<flux:breadcrumbs>` - Navigation
- `<flux:icon.*>` - Icons

## 🧪 Testing

Visit the following pages:
1. **Home** - `http://localhost:8001/`
2. **Product Detail** - Click any product
3. **Cart** - Click cart icon in header

Test the features:
- ✅ Search products
- ✅ View product details
- ✅ Add to cart
- ✅ Update quantities
- ✅ Remove items
- ✅ Checkout (simulated)
- ✅ AI recommendations appear after viewing 2+ products

## 🚀 Future Enhancements

- [ ] Integrate real AI API (OpenAI/Gemini)
- [ ] Add user authentication for saved carts
- [ ] Implement real payment gateway (Stripe)
- [ ] Add product categories and filters
- [ ] Implement order history
- [ ] Add product reviews and ratings
- [ ] Email notifications
- [ ] Admin panel for product management

## 📝 Notes

- Cart is session-based (cleared on browser close)
- Products use random images from Picsum
- AI recommendations use price similarity (fallback)
- No authentication required for shopping
- Checkout is simulated (no real payment)

---

Made with ❤️ using Laravel, Livewire, Alpine.js & Flux
