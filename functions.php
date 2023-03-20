<?php
/**
 * Get the bootstrap! If using the plugin from wordpress.org, REMOVE THIS!
 */

if ( file_exists( dirname( __FILE__ ) . '/cmb2/init.php' ) ) {
	require_once dirname( __FILE__ ) . '/cmb2/init.php';
} elseif ( file_exists( dirname( __FILE__ ) . '/CMB2/init.php' ) ) {
	require_once dirname( __FILE__ ) . '/CMB2/init.php';
}

/**
 * Conditionally displays a metabox when used as a callback in the 'show_on_cb' cmb2_box parameter
 *
 * @param  CMB2 $cmb CMB2 object.
 *
 * @return bool      True if metabox should show
 */


function pscm_get_image_count( $id ){
    global $wpdb;

    $att  = get_post_custom( $id );
    $file = $att['_wp_attached_file'][0];
    //Dot take full path as different image sizes could
    // have different month, year folders due to theme and image size changes
    $file = sprintf( "%s.%s",
        pathinfo( $file, PATHINFO_FILENAME ),
        pathinfo( $file, PATHINFO_EXTENSION )
    );

    $sql = "SELECT {$wpdb->posts}.ID 
        FROM {$wpdb->posts} 
        INNER JOIN {$wpdb->postmeta} 
        ON ({$wpdb->posts}.ID = {$wpdb->postmeta}.post_id) 
        WHERE {$wpdb->posts}.post_type IN ('post', 'page', 'event') 
        AND (({$wpdb->posts}.post_status = 'publish')) 
        AND ( ({$wpdb->postmeta}.meta_key = '_thumbnail_id' 
            AND CAST({$wpdb->postmeta}.meta_value AS CHAR) = '%d') 
            OR ( {$wpdb->posts}.post_content LIKE %s )
        ) 
        GROUP BY {$wpdb->posts}.ID";

    $prepared_sql = $wpdb->prepare( $sql, $id, "%src=\"%".$wpdb->esc_like( $file )."\"%" );

    $post_ids  = $wpdb->get_results( $prepared_sql );

    $count = count( $post_ids );

    return $count;
}
function pscm_not_allowed_to_delete($id){
    pscm_get_image_count($id);
} 
// function pscm_remove_media_delete_link_in_grid_view( $response, $attachment ) {
//     if ( pscm_not_allowed_to_delete( $attachment->ID ) )
//         $response['nonces']['delete'] = false;

//     return $response;
// }
// add_filter( 'wp_prepare_attachment_for_js', 'pscm_remove_media_delete_link_in_grid_view' );


add_filter( 'manage_media_columns', 'pscm_attached_objects_col', 10, 2);
 function pscm_attached_objects_col( $cols, $detached ) {
	$cols['attached_objects'] =  __( 'Attached Objects', 'pim-safe-category-media' );

	return $cols;
}

add_action( 'manage_media_custom_column', 'pscm_get_data_attached_objects' , 10, 2 );

function pscm_get_data_attached_objects( $col, $id ) {
	if($col == 'attached_objects'){
	
			$linked_objects = pscm_get_linked_objects( $id );
			$posts = $linked_objects['posts'];
			$terms = $linked_objects['terms'];

			foreach ( $posts as $post ){
				edit_post_link($post->ID, '<strong>', '</strong>, ', $post->ID);
            
			}

			foreach ( $terms as $term ){
				 edit_term_link($term->term_id, '<strong>', '</strong>, ', $term);
            
			}
		}
}

/**
 * Returns an array of of posts and terms arrays with IDs and post titles
 * of posts where the media library image is in use
 * as featured image or in the body of post or as term image
 * @param int $id
 * @return array
 */
function pscm_get_linked_objects( $id ){
	global $wpdb;

	$att  = get_post_custom( $id );
	$file = $att['_wp_attached_file'][0];
	// Do not take full path as different image sizes could
	// have different month, year folders due to theme and image size changes
	$name = pathinfo( $file, PATHINFO_FILENAME );
	$ext  = pathinfo( $file, PATHINFO_EXTENSION );

	$sql = <<<SQL
select distinct {$wpdb->posts}.ID, {$wpdb->posts}.post_type
from {$wpdb->posts}
inner join {$wpdb->postmeta}
on {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id
where
	post_type not in ( 'attachment', 'revision', 'nav_menu_item' )
	/*and ( {$wpdb->posts}.post_status = 'publish' )*/
	and (
		(
			{$wpdb->postmeta}.meta_key = '_thumbnail_id'
			and cast({$wpdb->postmeta}.meta_value as char) = '%d'
		)
		or ( {$wpdb->posts}.post_content like %s )
		/*or ( {$wpdb->term_meta}.meta_key ='pscm_term_avatar_id' )*/
	)
group by {$wpdb->posts}.ID
SQL;

 $response['posts'] = $wpdb->get_results( $wpdb->prepare(
		$sql,
		$id,
			"%src=\"%"
			. like_escape( $name )
			. "%"
			. like_escape( $ext )
			. "\"%"
	) );

	$response['terms'] = pscm_get_category_terms($id);
	
	return $response;

}

/**
 * Returns an array of terms with IDs 
 * where the media library image is in use
 * as term image
 * @param int $id
 * @return array
 */
function pscm_get_category_terms($id){

	$args = array(
        'taxonomy' => 'category',
        'meta_query' => array(
             array(
                'key'       => 'pscm_term_avatar_id',
                'value'     => $id,
                'compare'   => '='
             )
        )
    );

	$terms = get_terms( $args );
 
	return $terms;
}

add_action( 'admin_print_styles-upload.php', 'pscm_column_attached_objects' );
/**
 * Adjust Attached Objects column on Media Library page in WP admin
 */
function pscm_column_attached_objects() {
	echo
	'<style>
		.fixed .column-attached_objects {
			width: 10%;
		}
	</style>';
}

add_action( 'cmb2_admin_init', 'pscm_register_taxonomy_metabox' );
/**
 * Hook in and add a metabox to add fields to taxonomy terms
 */
function pscm_register_taxonomy_metabox() {

	/**
	 * Metabox to add fields to categories and tags
	 */
	$cmb_term = new_cmb2_box( array(
		'id'               => 'pscm_term_edit',
		'title'            => esc_html__( 'Category Metabox', 'cmb2' ), // Doesn't output for term boxes
		'object_types'     => array( 'term' ), // Tells CMB2 to use term_meta vs post_meta
		'taxonomies'       => array( 'category', 'post_tag' ), // Tells CMB2 which taxonomies should have these fields
		// 'new_term_section' => true, // Will display in the "Add New Category" section
	) );


	$cmb_term->add_field( array(
		'name' => esc_html__( 'Term Image', 'cmb2' ),
		'desc' => esc_html__( 'Select a JPEG or PNG image.', 'cmb2' ),
		'id'   => 'pscm_term_avatar',
		'type' => 'file',
		'query_args' => array(
			'type' => array(
				   'image/jpeg',
				'image/png',
				 )
		),
	) );


}
