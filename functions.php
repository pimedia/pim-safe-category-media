<?php
/**
 * Get the bootstrap! If using the plugin from wordpress.org, REMOVE THIS!
 */

 ini_set('display_errors', '1');
 ini_set('display_startup_errors', '1');
 error_reporting(E_ALL);

if ( file_exists( dirname( __FILE__ ) . '/cmb2/init.php' ) ) {
	require_once dirname( __FILE__ ) . '/cmb2/init.php';
} elseif ( file_exists( dirname( __FILE__ ) . '/CMB2/init.php' ) ) {
	require_once dirname( __FILE__ ) . '/CMB2/init.php';
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


add_filter( 'manage_media_columns', 'pscm_attached_objects_col', 10, 2);
 function pscm_attached_objects_col( $cols) {
	$cols['attached_objects'] =  __( 'Attached Objects', 'pim-safe-category-media' );

	return $cols;
}

add_action( 'manage_media_custom_column', 'pscm_get_data_attached_objects' , 10, 2 );

function pscm_get_data_attached_objects( $col, $id ) {
	if($col == 'attached_objects'){
	
		pscm_output_attached_objects($id);
		}
}

function pscm_output_attached_objects($id, $output='true'){
	$linked_objects = pscm_get_linked_objects( $id );
			$posts = $linked_objects['posts'];
			$terms = $linked_objects['terms'];

			$data = [];
			$terms_data = [];
			if(sizeof($posts)>0){
				
				$i=0;
				foreach ( $posts as $post ){
					$i++;
					$data[$i]['ID'] = $post->ID;
					$data[$i]['url'] = get_edit_post_link($post->ID, '');
				
				}
			}

			if( $output == false){
				$response = '';
				if(sizeof($posts)>0){
					$response = 'Articles<br>';
					$response .= pscm_create_list($data);
					$response .= '<br>'; 
				}
			
			   if(sizeof($terms)>0){
				
				$i=0;
				foreach ( $terms as $term ){
					$i++;
					$terms_data[$i]['ID'] = $term->term_id;
					$terms_data[$i]['url'] =  get_edit_term_link($term, 'category', '');
				
				}
			   }
			   if(sizeof($terms_data)>0){
				$response .= 'Terms<br>';
				$response .= pscm_create_list($terms_data);
				 }

				return $response;

			}else{
				if(sizeof($posts)>0){
					echo 'Articles<br>';
					echo pscm_create_list($data);
				echo '<br>'; 
				}
			
			   if(sizeof($terms)>0){
				
				$i=0;
				foreach ( $terms as $term ){
					$i++;
					$terms_data[$i]['ID'] = $term->term_id;
					$terms_data[$i]['url'] =  get_edit_term_link($term, 'category', '');
				
				}
			   }
			   if(sizeof($terms_data)>0){
				echo 'Terms<br>';
				echo pscm_create_list($terms_data);
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
		
	)
group by {$wpdb->posts}.ID
SQL;

 $response['posts'] = $wpdb->get_results( $wpdb->prepare(
		$sql,
		$id,
			"%src=\"%"
			.  $wpdb->esc_like( $name )
			. "%"
			.  $wpdb->esc_like( $ext )
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



add_filter('pre_delete_attachment', 'pscm_check_linked_images', 0, 2);

function pscm_check_linked_images($delete, $post) {

	
  if($post->ID){
	if ( !pscm_can_delete( $post->ID ) ){
		pscm_output_attached_objects($post->ID);	
	
		wp_die("<p><strong>Error</strong>: This image ($post->post_title) can't be deleted before removing it from linked above Articles!</p>");
	
	}
	   
       
    }
}


/**
 * Returns formatted output comma separated list 
 * where the media library image is in use
 * as term image
 * @param array $data
 * @return html output
 */
function pscm_create_list($data){
	$len = sizeof($data);
	$output= '';
	if($len>0){
			$i = 0;
            foreach($data as $d){
				$i++;
				$output .= '<a href="'.$d['url'].'">'.$d['ID'].'</a>';

				if ($i != $len) $output .= ', ';
			}
		}
	return $output;
}

add_action('rest_api_init', function () {
	register_rest_route( '/assignment/v1/', '/image/(?P<id>\d+)',array(
				  'methods'  => 'GET',
				  'callback' => 'pscm_api_attachment_details'
		));
  });


/**
 * Returns details of a given image ID in json format
 * @param array $request
 * @return json
 */
  function pscm_api_attachment_details($request){

	$image_id = $request['id'];

	$image = get_post($image_id);

	if (empty($image)) {
		return new WP_Error( 'error image', 'There is no image by this id', array('status' => 404) );
	
		}

		$image_meta = wp_get_attachment_metadata($image->ID);
		$attached_objects = pscm_get_linked_objects($image->ID);

		$res = new stdClass();

		$res->ID = $image->ID;
		$res->date =  $image->post_date;
		$res->slug =  $image_meta['file'];

		$res->type = $image->post_mime_type;
		$res->link = $image->guid;
		$res->alt_text = $image->post_title;
		$res->attached_objects =  $attached_objects;

	
		$response = new WP_REST_Response($res);
		$response->set_status(200);
	
		return $response;

  }

  add_action('rest_api_init', function () {
	register_rest_route( '/assignment/v1/', '/delete_image/(?P<id>\d+)',array(
				  'methods'  => 'DELETE',
				  'callback' => 'pscm_api_delete_attachment'
		));
  });


/**
 * Returns confirmation of deleted image ID in json format
 * @param array $request
 * @return json
 */
  function pscm_api_delete_attachment($request){

	$image_id = $request['id'];

	$image = get_post($image_id);

	if (empty($image)) {
		return new WP_Error( 'error image', 'There is no image by this id', array('status' => 404) );
	
		}

		$attached_objects = pscm_get_linked_objects($image->ID);

		if( sizeof($attached_objects['posts'])>0 || sizeof($attached_objects['terms'])>0){
			return new WP_Error( 'error deletion', 'Deletion failed as this image is being used with articles', array('status' => 200) );
		}

		
		$res = wp_delete_attachment( $image->ID, true);
        if($res->ID) $message = 'Media ID: '.$res->ID.' deleted successfully.'; 
		else $message = 'Deleted successfully';
		$response = new WP_REST_Response( array( 'message' => $message ));
		$response->set_status(200);
	
		return $response;

  }
  

function pscm_add_media_attachment_detail($form_fields, $post) {
		
    $form_fields['attached_objects'] = array(
        'label' => __( 'Linked Articles' ),
		'input' => 'html',
		'html' => '<span></span>',
		 'helps' => pscm_output_attached_objects($post->ID, false)
    );
    return $form_fields;
}


add_filter('attachment_fields_to_edit', 'pscm_add_media_attachment_detail', 10, 2);

function pscm_delete_link_in_list_view( $actions ) {
	global $post;
    if ( !pscm_can_delete( $post->ID ) ){
		 $actions['delete'] = '<a href="'.get_delete_post_link($post->ID, '', true).'" class="submitdelete aria-button-if-js" role="button">'. __('Can\'t Delete Permanently') .'</a>';
	}
       
    return $actions;
}
add_filter( 'media_row_actions', 'pscm_delete_link_in_list_view' );

function pscm_remove_bulk_delete_in_list_view( $actions ) {
	
    unset( $actions['delete'] );
	
    return $actions;
}
add_filter( 'bulk_actions-upload', 'pscm_remove_bulk_delete_in_list_view' );

function pscm_remove_delete_link_in_grid_view( $response, $attachment ) {
	
    if ( !pscm_can_delete( $attachment->ID ) ){
		$response['nonces']['delete'] = false;

	}
    return $response;
}
//add_filter( 'wp_prepare_attachment_for_js', 'pscm_remove_delete_link_in_grid_view' );

function pscm_can_delete($id) {
    
	$attached_objects = pscm_get_linked_objects($id);
    if( sizeof($attached_objects['posts'])>0 || sizeof($attached_objects['terms'])>0){
		$response = false;
	}else $response = true;

	return $response;
}
