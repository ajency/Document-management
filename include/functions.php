<?php
// include the plugin api functionality 
require_once( 'api.php');
/*
 * Custom general functions of plugin
 * 
 */

/*
 * handling document upload from plugin interface
 */
function ajdm_handle_document_upload($upload_calback,$post_id){
    // change the default path of uploads to plugin specific path
    add_filter( 'upload_dir', 'change_document_upload_path', 100, 1 );
    
    chmod(WP_CONTENT_DIR.DIRECTORY_SEPARATOR.'ajdm_uploads', 0755);  
    $doc_id = media_handle_upload( 'async-upload', $post_id );
    chmod(WP_CONTENT_DIR.DIRECTORY_SEPARATOR.'ajdm_uploads', 0000);   
    
    return $doc_id;
}

/*
 * filter function to change the uploads directory path
 */
function change_document_upload_path ($upload){
    $document_path = "ajdm_uploads";
    $upload['path'] = WP_CONTENT_DIR .DIRECTORY_SEPARATOR .$document_path;
    $upload['url'] = WP_CONTENT_URL .'/'.$document_path;
    $upload['basedir'] = WP_CONTENT_DIR .DIRECTORY_SEPARATOR .$document_path;
    $upload['baseurl'] = WP_CONTENT_URL .'/' .$document_path;;
    $upload['subdir'] = '';

    return $upload;
}

/*
 * function to generate unique name for the document with name of file as prefix
 * hoook to filter wp_handle_upload_prefilter
 */
function ajdm_upload_document_name( $file )
{
    if( isset($_POST['document_form'])){
        $file_details = explode(".", $file['name']);
        $extension = end($file_details);
        reset($file_details);
        $file['name'] = uniqid(current($file_details)).'.'.$extension;
    }
    return $file;
}
add_filter( 'wp_handle_upload_prefilter', 'ajdm_upload_document_name' );   

function ajdm_document_exists( $document_id, $post_type='attachment' ) {
	global $wpdb;
 
	$query = "SELECT ID FROM $wpdb->posts WHERE 1=1";
	$args = array();
 
	if ( !empty ( $document_id ) ) {
	     $query .= " AND ID = '%d' ";
	     $args[] = $document_id;
	}
	if ( !empty ( $post_type ) ) {
	     $query .= " AND post_type = '%s' ";
	     $args[] = $post_type;
	}
 
	if ( !empty ( $args ) )
	     return $wpdb->get_var( $wpdb->prepare($query, $args) );
 
	return 0;
}
