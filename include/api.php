<?php
/*
 * Api configuration and methods of the plugin
 * 
 */

include_once( ABSPATH . 'wp-admin/includes/plugin.php' ); 
if(is_plugin_active('json-rest-api/plugin.php')){
    
    /*
     * function to configure the plugin api routes
     */
    function documentmanagement_plugin_api_init($server) {
        global $documentmanger_plugin_api;

        $documentmanger_plugin_api = new DocumentManagerAPI($server);
        add_filter( 'json_endpoints', array( $documentmanger_plugin_api, 'register_routes' ) );
    }
    add_action( 'wp_json_server_before_serve', 'documentmanagement_plugin_api_init',10,1 );

    class DocumentManagerAPI {

        /**
         * Server object
         *
         * @var WP_JSON_ResponseHandler
         */
        protected $server;

        /**
         * Constructor
         *
         * @param WP_JSON_ResponseHandler $server Server object
         */
        public function __construct(WP_JSON_ResponseHandler $server) {
                $this->server = $server;
        }

        /*Register Routes*/
        public function register_routes( $routes ) {

             $routes['/ajdm/document/addtofolder'] = array(
                array( array( $this, 'add_to_folder'), WP_JSON_Server::CREATABLE | WP_JSON_Server::ACCEPT_JSON),
                );
             
             $routes['/ajdm/document/download/(?P<document_id>\d+)'] = array(
                array( array( $this, 'download_document'), WP_JSON_Server::READABLE ),
                );
             
             $routes['/ajdm/document/delete/(?P<document_id>\d+)'] = array(
                array( array( $this, 'delete_document'), WP_JSON_Server::DELETABLE ),
                );

             $routes['/ajdm/doc/upload'] = array(
                array( array( $this, 'upload_doc'), WP_JSON_Server::CREATABLE),
                );
            
            return $routes;
        }
        
        /*
         * function to associate an attachment to taxonomy
         * @param array $data $_POST array
         * 
         * @returns response on success of associating an attachment to taxonomy| error response
         */
        public function add_to_folder($data){
            
            global $aj_documentmanager;
            $document_id = intval($data['document_id']);
            $folder_slug = $data['folder_slug'];
            
            //validate the document type
            if(is_null(ajdm_document_exists($document_id)))
                wp_send_json(array('code'=>'ERROR','msg'=>'Invalid Document Id'));
            
            $term_data = get_term_by( 'slug', $folder_slug, 'folder', ARRAY_A );
            
            //validate the folder slug
            if(!$term_data){
                wp_send_json(array('code'=>'ERROR','msg'=>'Invalid folder slug'));
            }
            
            $term_document_type = get_option($aj_documentmanager::$plugin_prefix."_folder_".$term_data['term_id']);
            
            update_post_meta($document_id, 'doc_type', $term_document_type);
            
            $term_taxonomy_id = wp_set_object_terms( $document_id, $term_data['term_taxonomy_id'], 'folder', true );
            
            $response = array('code'=>'OK','msg' => 'associated document to folder','term_taxonomy_id' =>$term_taxonomy_id);  
            
            wp_send_json($response);
        }
        
        /*
         * download document given an id
         * @param int $document_id
         * 
         * @return file download if document exists | error response  
         */
        public function download_document($document_id){
            
            $document_id = intval($document_id);
            //validate the document type
            if(is_null(ajdm_document_exists($document_id)))
                wp_send_json(array('code'=>'ERROR','msg'=>'Invalid Document Id'));
            
            $document_name = get_post_meta($document_id,'_wp_attached_file',true);
            $file_details = explode(".", $document_name);
            $extension = end($file_details);
            
            $document_post_data = get_post($document_id);
       
            chmod(WP_CONTENT_DIR.DIRECTORY_SEPARATOR.'ajdm_uploads', 0755);  
            $content_dir = WP_CONTENT_DIR;
            $path_to_file = $content_dir.DIRECTORY_SEPARATOR.'ajdm_uploads'.DIRECTORY_SEPARATOR.$document_name;
            
            $display_document_name = $document_post_data->post_title.'.'.$extension;
            
            header("Content-Type: ".get_post_mime_type( $document_id )."");
            header("Content-Transfer-Encoding: Binary");
            header("Content-Length:".filesize($path_to_file));
            header("Content-Disposition: attachment; filename=".$display_document_name."");
            readfile($path_to_file);
            chmod(WP_CONTENT_DIR.DIRECTORY_SEPARATOR.'ajdm_uploads', 0000);  
            exit;
        }
        
        /*
         * delete document given an id
         * @param int $document_id
         * 
         * @return success response on document delete | error response  
         */        
        public function delete_document($document_id){
            
            $document_id = intval($document_id);
            //validate the document type
            if(is_null(ajdm_document_exists($document_id)))
                wp_send_json(array('code'=>'ERROR','msg'=>'Invalid Document Id'));  
            
            // change the default path of uploads to plugin specific path for document delete physical file
            add_filter( 'upload_dir', 'change_document_upload_path', 100, 1 );
            
            $result = wp_delete_post( $document_id, true );

            if ( ! $result ) {
                    return new WP_Error( 'json_cannot_delete', __( 'The document cannot be deleted.' ), array( 'status' => 500 ) );
            }    
            
            wp_send_json( array( 'code' =>'OK', 'message' => __( 'Deleted the document' ) ));
        }

        public function upload_doc(){
            $attachment  = upload_file_to_temp();

            if(is_wp_error($attachment)){
                $status = 400;

                $response_data = array('code' => $response->get_error_code(),'message' => $response->get_error_message());
                
                $response = new WP_JSON_Response( $response );

                $response->set_data($response_data);
                $response->set_status($status);

            }
            else
            {
                // create a document post entry for this attachment

                $document_data = array(
                    'post_title' => $attachment['fileName'], 
                    'guid' => $attachment['filepath'],
                    'post_mime_type' => $attachment['fileMimeType']

                    );

                $document_id = ajdm_create_document_post($document_data);

                if(is_wp_error($document_id)){
                    $status = 400;

                    $response_data = array('code' => $document_id->get_error_code(),'message' => $response->get_error_message());

                    $response = new WP_JSON_Response();

                    $response->set_data($response_data);
                    $response->set_status($status);
                }
                else{
                   $data = array('code'=> 'document_created', 'message'=> 'Document was created', 'data'=> array('attachment_id'=>$document_id));
                   $response = new WP_JSON_Response( $data, 201 ); 
                }

            } 

            return $response; 
        }
        
    }

}
