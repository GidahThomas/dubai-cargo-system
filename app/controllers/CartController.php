<?php

/**
 * Customer shopping cart, kept in the session as [product id => quantity].
 * Checkout turns the whole cart into one order (see Order::createFromItems).
 */
class CartController extends Controller
{
    private const MAX_QUANTITY = 99;

    public function index(): void
    {
        $this->requireRole('customer');

        $this->view('cart/index', [
            'pageTitle' => 'My Cart',
            'lines' => self::lines(),
        ]);
    }

    public function add(): void
    {
        $this->requireRole('customer');
        $this->validateCsrf();

        $productId = (int) $this->post('product_id');
        $quantity = max(1, min(self::MAX_QUANTITY, (int) $this->post('quantity', 1)));
        $product = $productId > 0 ? (new Product())->find($productId) : null;

        if (!$product || $product['status'] !== 'active') {
            flash('error', 'That product is not available.');
            $this->redirect('products');
        }

        $cart = self::cart();
        $cart[$productId] = min(self::MAX_QUANTITY, ($cart[$productId] ?? 0) + $quantity);
        $_SESSION['cart'] = $cart;

        flash('success', $product['name'] . ' added to your cart.');
        $this->redirect($this->post('return_to') === 'cart' ? 'cart' : 'products');
    }

    public function update(): void
    {
        $this->requireRole('customer');
        $this->validateCsrf();

        $cart = [];
        foreach ((array) ($_POST['quantities'] ?? []) as $productId => $quantity) {
            $productId = (int) $productId;
            $quantity = min(self::MAX_QUANTITY, (int) $quantity);
            if ($productId > 0 && $quantity > 0) {
                $cart[$productId] = $quantity;
            }
        }
        $_SESSION['cart'] = $cart;

        flash('success', 'Cart updated.');
        $this->redirect('cart');
    }

    public function remove(int $productId): void
    {
        $this->requireRole('customer');
        $this->validateCsrf();

        $cart = self::cart();
        unset($cart[$productId]);
        $_SESSION['cart'] = $cart;

        $this->redirect('cart');
    }

    public function checkout(): void
    {
        $this->requireRole('customer');
        $this->validateCsrf();

        $address = $this->cleanString($this->post('shipping_address'));
        $notes = $this->cleanString($this->post('notes'));

        if (!self::cart()) {
            flash('error', 'Your cart is empty.');
            $this->redirect('products');
        }

        if ($address === '') {
            flash('error', 'Please enter a delivery address.');
            $this->redirect('cart');
        }

        try {
            $orderModel = new Order();
            $orderId = $orderModel->createFromItems(Auth::id(), self::cart(), $address, $notes ?: null);
            $order = $orderModel->find($orderId);
        } catch (Throwable $exception) {
            flash('error', 'Order could not be placed: ' . $exception->getMessage());
            $this->redirect('cart');
        }

        $_SESSION['cart'] = [];

        foreach ((new User())->byRoles(['manager', 'admin']) as $staff) {
            (new Notification())->create((int) $staff['id'], 'New order placed', 'Order ' . $order['order_number'] . ' is waiting for review.', 'info');
        }
        (new AuditLog())->create(Auth::id(), 'order_created', 'orders', $orderId, $order['order_number']);

        flash('success', 'Order ' . $order['order_number'] . ' placed. Next, submit your payment on the Payments page.');
        $this->redirect('payments');
    }

    /**
     * @return array<int, int>
     */
    public static function cart(): array
    {
        return array_map('intval', (array) ($_SESSION['cart'] ?? []));
    }

    public static function count(): int
    {
        return array_sum(self::cart());
    }

    /**
     * Cart rows joined with current product data; unavailable products are dropped.
     */
    public static function lines(): array
    {
        $lines = [];
        $productModel = new Product();

        foreach (self::cart() as $productId => $quantity) {
            $product = $productModel->find((int) $productId);
            if (!$product || $product['status'] !== 'active') {
                continue;
            }
            $lines[] = [
                'product' => $product,
                'quantity' => $quantity,
                'line_total' => (float) $product['price'] * $quantity,
            ];
        }

        return $lines;
    }
}
