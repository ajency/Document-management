<?php
/*
 * Custom functions of registering Document types
 * 
 * function to register document type
 * @param string $document_type doc type name 
 * 
 */
function register_document_types($document_type = ''){
    global $ajdm_doctypes;
    
    $ajdm_document_types = array();
    //get the hooked Document types and assign to global variable
    $ajdm_doctypes = apply_filters('ajdm_document_types_filter',$ajdm_document_types);
    if($document_type != ''){
        if(empty($ajdm_doctypes)){
            $ajdm_doctypes[] = $document_type;
        }else{
            if(!in_array($document_type, $ajdm_doctypes))
                    $ajdm_doctypes[] = $document_type;
        }
    }
}

/*
 * hook function to get the theme defined csv components
 */
function theme_defined_document_types($ajdm_doc_types){
    $defined_document_types = array();  // theme defined document types array  ie format array('users','communication','business_proposal')
    $defined_document_types = apply_filters('add_document_types_filter',$defined_document_types);
    
    foreach($defined_document_types as $doc_type){
            if(!in_array($doc_type, $ajdm_doc_types))
                $ajdm_doc_types[] = $doc_type;
    }

    return $ajdm_doc_types;
    
}
add_filter('ajdm_document_types_filter','theme_defined_document_types',10,1);
