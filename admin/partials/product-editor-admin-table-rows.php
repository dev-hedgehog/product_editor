<?php
/**
 * This file is a chunk that render rows of products table
 *
 * @link       https://github.com/dev-hedgehog/product-editor
 * @since      1.0.0
 *
 * @package    Product-Editor
 * @subpackage Product_Editor/admin/partials
 */

/** @var WC_Product_Simple[]|WC_Product_Variable[]|WC_Product_Grouped[] $products */
/** @var int $show_variations Should show variations in variable products */

foreach ( $products as $product ) {
	$is_variable = is_a( $product, 'WC_Product_Variable' );
	$is_simple   = is_a( $product, 'WC_Product_Simple' );
	?>
	<tr class="<?php echo $is_variable ? 'variable-product' : 'simple-product'; ?>" data-id="<?php echo esc_attr( $product->get_id() ); ?>">
		<td><input class="cb-pr" name="ids[]" value="<?php echo esc_attr( $product->get_id() ); ?>" type="checkbox"></td>
		<td>
		<?php
		echo $is_variable
							? '<input class="cb-vr-all-parent ' . ( $show_variations ? 'expand' : 'collapse' ) . '" data-id="' . esc_attr( $product->get_id() ) . '" data-children_ids="' . esc_attr( wp_json_encode( $product->get_children() ) ) . '" type="checkbox">'
							. '<label class="lbl-toggle"></label>'
							: ''
		?>
							</td>
		<td><?php echo esc_html( $product->get_id() ); ?></td>
		<td class="td-name"><?php echo esc_html( $product->get_name() ); ?></td>
		<td><?php echo esc_html( $product->get_status() ); ?></td>
		<td><?php $is_variable ? esc_html_e( 'Variable', 'product-editor' ) : esc_html_e( 'Simple', 'product-editor' ); ?></td>
		<td class="td-price"><?php echo $product->get_price_html(); ?></td>
		<td class="td-regular-price <?php echo $is_variable ? '' : 'editable'; ?>"><?php echo esc_html( $product->get_regular_price( 'edit' ) ); ?></td>
		<td class="td-sale-price <?php echo $is_variable ? '' : 'editable'; ?>"><?php echo esc_html( $product->get_sale_price( 'edit' ) ); ?></td>
		<td class="td-akciya editable"><?php echo ! $product->get_meta( 'sale' ) ? 'Нет' : 'Да'; ?></td>
	</tr>
	<?php
	if ( $is_variable && $show_variations ) {
		include 'product-editor-admin-table-variations-rows.php';
	}
}
?>
