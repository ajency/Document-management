<?php
/*
 * Custom general functions of plugin
 * 
 */

/*
 * handling document upload from plugin interface
 */
function ajdm_handle_document_upload($upload_calback,$post_id){
    add_filter( 'upload_dir', 'change_document_upload_path', 100, 1 );
    $doc_id = media_handle_upload( 'async-upload', $post_id );
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
