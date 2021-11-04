<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Product_Editor
 * @subpackage Product_Editor/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Product_Editor
 * @subpackage Product_Editor/admin
 * @author     Your Name <email@example.com>
 */
class Product_Editor_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Product_Editor_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Product_Editor_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/product-editor-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Product_Editor_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Product_Editor_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/product-editor-admin.js', array( 'jquery' ), $this->version, false );

	}


	public function admin_menu() {
    add_submenu_page('edit.php?post_type=product', 'Редактор продуктов', 'Редактор продуктов',
      'manage_options', 'product-editor', [$this, 'main_page']);
    /*add_menu_page('My Custom Page', 'My Custom Page', 'manage_options', 'my-top-level-slug');
    add_submenu_page( 'my-top-level-slug', 'My Custom Page', 'My Custom Page',
      'manage_options', 'my-top-level-slug', [$this, 'main_page']);*/
  }

  public function main_page() {

    global $wpdb;
    global $wp_query;
    $html = '';
    $product_categories = get_terms(['taxonomy'   => 'product_cat',]);


    //$wpdb->query( "START TRANSACTION" );
/*
    $p = wc_get_product(14614);
    echo '<b>'.$p->get_name().' price: '.$p->get_price().' regular_price: '.$p->get_regular_price().' sale_price: '.$p->get_sale_price()."</b>\n\n\n";
    print_r($p->get_meta('sale'));
    //print_r($p->get_meta_data());
    //$p->set_meta_data(['sale' => ' ']);
    $p->update_meta_data('sale', ['Товар по акции']);
    $p->set_price('19903');
    $p->set_regular_price('19903');
    $p->set_sale_price('19901');
    $p->save();

    $p = wc_get_product(14614);
    echo '<b>'.$p->get_name().' price: '.$p->get_price().' regular_price: '.$p->get_regular_price().' sale_price: '.$p->get_sale_price()."</b>\n\n\n";
*/
    $args = [
      'paginate' => true,
      //  'type' => 'simple'
      //  'category' => ['']
      //  's' => 'KAWS'
    ];
    $args['limit'] = General_Helper::getVar('limit', 10);
    $args['offset'] = (General_Helper::getVar('paged', 1)-1) * $args['limit'];
    General_Helper::getVar('product_cat', false) && $args['category'] = [General_Helper::getVar('product_cat')];
    General_Helper::getVar('s', false) && $args['name'] = General_Helper::getVar('s');
    $results = wc_get_products($args);
    if ($results->total === 0 && $args['name']) {
      $args['s'] = $args['name'];
      unset($args['name']);
      $results = wc_get_products($args);
    }
    $total = $results->total;
    $num_of_pages = $results->max_num_pages;
    $products = $results->products;
    $num_on_page = sizeof($products);
    $show_variations = General_Helper::getVar('show_variations');


    include ('partials/product-editor-admin-display.php');
  }

  public static $changeActions = [
    'change_regular_price' => 'change_regular_price',
    'change_sale_price' => 'change_sale_price',
    'change_akciya'  => 'change_akciya',
  ];

	public function action_expand_product_variable() {
    if (!($id = General_Helper::getVar('id')) || !($product = wc_get_product($id)) || !is_a($product, 'WC_Product_Variable')) {
      self::sendResponse('', 200, 'raw');
    }

    self::sendResponse(include ('partials/product-editor-admin-table-variations-rows.php'), 200, 'raw');
	}

  public function action_bulk_changes() {
    $isEmpty = true;
    $ids = General_Helper::postVar('ids');
    foreach (self::$changeActions as $action_name => $func_name) {
      if (General_Helper::postVar($action_name)) {
        $isEmpty = false;
      }
    }
    if ($isEmpty || empty($ids)) {
      self::sendResponse();
    }

    global $wpdb;
    $wpdb->query("START TRANSACTION");

    foreach ($ids as $id) {
      $product = wc_get_product($id);
      if (!$product) {
        self::sendResponse(['message' => 'Продукт с id:'.$id.' не найден. Операции отменены.'], 500);
      }
      self::process_change_product($product);
    }
    $wpdb->query("COMMIT");

    // reload products data
    self::sendResponse(self::response_data_for_ids($ids));
  }

  private static function process_change_product($product) {
    foreach (self::$changeActions as $action_name => $func_name) {
      if (General_Helper::postVar($action_name)) {
        self::$func_name($product);
      }
    }
    $product->save();
  }

  private static function response_data_for_ids($ids) {
    $response_data = [];
    $extra_ids = [];
    foreach ($ids as $id) {
      $product = wc_get_product($id);

      if (is_a($product, 'WC_Product_Variation') && !in_array($product->get_parent_id(), $ids) && !in_array($product->get_parent_id(), $extra_ids)) {
        $extra_ids[] = $product->get_parent_id();
        $response_data[] = self::response_data_for_product(wc_get_product($product->get_parent_id()));
      }

      $response_data[] = self::response_data_for_product($product);
    }
    return $response_data;
  }

  private static function response_data_for_product($product) {
    return [
      'id' => $product->get_id(),
      'price' => $product->get_price_html(),
      'regular_price' => $product->get_regular_price(),
      'sale_price' => $product->get_sale_price(),
      'akciya' => is_a($product, 'WC_Product_Variation') ? '' : (!$product->get_meta('sale')? 'Нет': 'Да'),
    ];// is_a($product, 'WC_Product_Variation') ? '' :
  }

  private static function change_akciya($product) {
    $action = General_Helper::postVar('change_akciya');
    if (empty($action) || is_a($product, 'WC_Product_Variation')) {
      return;
    }

    switch ((int)$action) {
      case 1: $product->update_meta_data('sale', ['Товар по акции']);
      break;
      case 2: $product->update_meta_data('sale', '');
    }
  }

  private static function change_sale_price($product) {
    $arg_sale_price = trim(General_Helper::postVar('_sale_price', 0));
    $action = General_Helper::postVar('change_sale_price');
    if (empty($action)) {
      return;
    }
    $isPercentage = stripos($arg_sale_price, '%') !== false;
    $arg_sale_price = preg_replace('/[^\d\.\-]/', '', $arg_sale_price);
    $regular_price = $product->get_regular_price();
    $old_sale_price = (float)$product->get_sale_price();
    $new_sale_price = $old_sale_price;
    $number = (float) wc_format_decimal($arg_sale_price);
    switch ((int)$action) {
      case 1:
        $new_sale_price = $number;
        break;
      case 2:
        $new_sale_price = $old_sale_price + ($isPercentage ? $old_sale_price/100*$number : $number);
        break;
      case 3:
        $new_sale_price = $old_sale_price - ($isPercentage ? $old_sale_price/100*$number : $number);
        break;
      case 4:
        $new_sale_price = $regular_price - ($isPercentage ? $regular_price/100*$number : $number);
        break;
    }
    if ($new_sale_price <= 0) {
      $new_sale_price = '';
    }
    $product->set_sale_price($new_sale_price);
  }

  private static function change_regular_price($product) {
    $arg_regular_price = trim(General_Helper::postVar('_regular_price'));
    $action = General_Helper::postVar('change_regular_price');
    if (empty($action)) {
      return;
    }
    $isPercentage = stripos($arg_regular_price, '%') !== false;
    $arg_regular_price = preg_replace('/[^\d\.\-]/', '', $arg_regular_price);
    $old_regular_price = $product->get_regular_price();
    $new_regular_price = $old_regular_price;
    $number = (float) wc_format_decimal($arg_regular_price);
    switch ((int)$action) {
      case 1:
        $new_regular_price = $number;
        break;
      case 2:
        $new_regular_price = $old_regular_price + ($isPercentage ? $old_regular_price/100*$number : $number);
        break;
      case 3:
        $new_regular_price = $old_regular_price - ($isPercentage ? $old_regular_price/100*$number : $number);
        break;
    }
    if ($new_regular_price <= 0 || $new_regular_price == '') {
      self::sendResponse(
        ['message' => 'Для продукта '.$product->get_name().' вычислена недопустимая цена: "'.$new_regular_price.'". Операции отменены.'],409);
    }
    $product->set_regular_price($new_regular_price);
  }

  private static function sendResponse($body = [], $code = 200, $format='json') {
    status_header($code);
    exit($format=='json'? json_encode($body) : $body);
  }
}
