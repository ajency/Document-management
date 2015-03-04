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

function upload_file_to_temp($data){
    // Make sure file is not cached (as it happens for example on iOS devices)
    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
    header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
    header("Cache-Control: no-store, no-cache, must-revalidate");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");

    /* 
    // Support CORS
    header("Access-Control-Allow-Origin: *");
    // other CORS headers if any...
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        exit; // finish preflight CORS requests here
    }
    */

    // 5 minutes execution time
    @set_time_limit(5 * 60);

    // Uncomment this one to fake upload time
    // usleep(5000);
      
        
    $cleanupTargetDir = true; // Remove old files
    $maxFileAge = 5 * 3600; // Temp file age in seconds

    $upload_dir = wp_upload_dir();
    $base_upload_dir = $upload_dir['basedir'];

    $targetDir = $base_upload_dir.'/tmp_phoenix';

    if (!file_exists($targetDir)) {
        @mkdir($targetDir);
    }

    // Get a file name
    if (isset($_REQUEST["name"])) {
        $fileName = $_REQUEST["name"];
    } elseif (!empty($_FILES)) {
        $fileName = $_FILES["async-upload"]["name"];
    } else {
        $fileName = uniqid("file_");
    }

    // $fileName=$_REQUEST['frmid']."_".$_REQUEST['fld']."_".$fileName;

    $filePath = $targetDir . DIRECTORY_SEPARATOR . $fileName;

    // Chunking might be enabled
    $chunk = isset($_REQUEST["chunk"]) ? intval($_REQUEST["chunk"]) : 0;
    $chunks = isset($_REQUEST["chunks"]) ? intval($_REQUEST["chunks"]) : 0;


    // Remove old temp files    
    if ($cleanupTargetDir) {
        if (!is_dir($targetDir) || !$dir = opendir($targetDir)) {
            return new WP_Error( 'failed_to_open_temp', __( 'Failed to open temp directory.' ));
            // die('{"jsonrpc" : "2.0", "error" : {"code": 100, "message": "Failed to open temp directory."}, "id" : "id"}');
            // echo '{"jsonrpc" : "2.0", "error" : {"code": 100, "message": "Failed to open temp directory."}, "id" : "id"}' ;
        }

        while (($file = readdir($dir)) !== false) {
            $tmpfilePath = $targetDir . DIRECTORY_SEPARATOR . $file;

            // If temp file is current file proceed to the next
            if ($tmpfilePath == "{$filePath}.part") {
                continue;
            }

            // Remove temp file if it is older than the max age and is not the current file
            //if (preg_match('/\.part$/', $file) && (filemtime($tmpfilePath) < time() - $maxFileAge)) {
            if ( (filemtime($tmpfilePath) < time() - $maxFileAge)) {
                @unlink($tmpfilePath);
            }
        }
        closedir($dir);
    }   


    // Open temp file
    if (!$out = @fopen("{$filePath}.part", $chunks ? "ab" : "wb")) {
        return new WP_Error( 'failed_to_open_output_stream', __( 'Failed to open output stream.' ));
        // die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}');
        // echo '{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}';
    }

    if (!empty($_FILES)) {
        if ($_FILES["async-upload"]["error"] || !is_uploaded_file($_FILES["async-upload"]["tmp_name"])) {
            return new WP_Error( 'failed_to_move', __( 'Failed to move uploaded file.' ));
            // die('{"jsonrpc" : "2.0", "error" : {"code": 103, "message": "Failed to move uploaded file."}, "id" : "id"}');
            // echo '{"jsonrpc" : "2.0", "error" : {"code": 103, "message": "Failed to move uploaded file."}, "id" : "id"}';
        }

        // Read binary input stream and append it to temp file
        if (!$in = @fopen($_FILES["async-upload"]["tmp_name"], "rb")) {
            return new WP_Error( 'failed_to_open_input', __( 'Failed to open input stream' ));
            // die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');
            // echo '{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}';
        }
    } else {    
        if (!$in = @fopen("php://input", "rb")) {
            return new WP_Error( 'failed_to_open_input', __( 'Failed to open input stream' ));
            // die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');
            // echo '{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}';
        }
    }

    while ($buff = fread($in, 4096)) {
        fwrite($out, $buff);
    }

    @fclose($out);
    @fclose($in);

    // Check if file has been uploaded
    if (!$chunks || $chunk == $chunks - 1) {
        // Strip the temp .part suffix off 
        rename("{$filePath}.part", $filePath);
    }

    // Return Success JSON-RPC response
    // die('{"jsonrpc" : "2.0", "result" : null, "id" : "id","cleanFileName": "'.$fileName.'"}');
    $result = array('fileName' => $fileName , 'filepath' => $filePath , 'fileMimeType' => $_FILES["async-upload"]["type"]);

    return $result;
}

function ajdm_create_document_post($document_data){


    $defaults = array(
      'post_status'           => 'draft', 
      'post_type'             => 'document',
      'post_author'           => get_current_user_id(),
      'ping_status'           => get_option('default_ping_status'), 
      'post_parent'           => 0,
      'menu_order'            => 0,
      'to_ping'               => '',
      'pinged'                => '',
      'post_password'         => '',
      'post_content_filtered' => '',
      'post_excerpt'          => '',
      'import_id'             => 0
      );

    $post = wp_parse_args( $document_data, $defaults );

    $document_id = wp_insert_post( $post, true );

    return $document_id;
}

function ajdm_update_document_meta($document_meta){

    return $document_id;
}


