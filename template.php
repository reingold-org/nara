<?php

/**
 * @file
 * template.php
 */

/**
 * Function that checks for validity of exit links passed via the $_REQUEST['link']
 *
 * @author      Theo Skye Welch <skyeflye@skyeflye.com>
 * @version     1.0.0
 * @return      boolean   true|false
 */
function nara_okay_to_exit() {
  $lower_link = strtolower($_GET['link']);

  // if there was no link provided or no referrer
  if ( !isset($_SERVER['HTTP_REFERER']) || $_SERVER['HTTP_REFERER'] == '' || strpos($lower_link, 'script') || strpos($lower_link, 'iframe')) {
    // if there was no referrer provided by the browser, we don't honor the forwarding. sorry. no dice.
    return FALSE;
  }
  else {

    // Note: this domain extraction process should work for IP
    //    addresses (websites without domain names) but
    //    it hasn't been tested (and it's not very important).

    // extract the *main* portion of the domain from the referrer
    // grab the segments of the referrer string
    $segments = parse_url($_SERVER['HTTP_REFERER']);
    // if the hostname is an IP address
    if (preg_match('/\b\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\b/', $segments['host'])) {
      $main_domain = $segments['host'];
    }
    else {
      // grab the components of the hostname
      $hostname_pieces = explode('.',$segments['host']);
      // assemble the main domain from the second-to-last and last elements of the $hostname_pieces array
      $main_domain = $hostname_pieces[count($hostname_pieces)-2] . '.' . $hostname_pieces[count($hostname_pieces)-1];
    }

  }

  // create a list of the safe domain names that we allow exit links from
  $valid_domains = array(
    'archives.gov', // [anything.]archives.gov
    'nara-at-work.gov', // [anything.]nara-at-work.gov
    'nara.gov', // [anything.]nara.gov
    'docteach.org', // [anything.]docteach.org
    'newsig.com', // [anything.]newsig.com
    'archivesdrupaldev.com',
    $_SERVER['SERVER_NAME'] // this server's domain name (or IP address)
  );
  // set a default flag for whether referrer is valid or not
  $referrer_is_valid = FALSE;

  // check against each of the above domain names
  // If a match is found, set the flag to true and break out of the loop.
  foreach($valid_domains as $domain) {
    if ( $main_domain == $domain ) {
      $referrer_is_valid = TRUE;
      break;
    }
  }
  return $referrer_is_valid;
}

function nara_unhtmlentities($string)
{
/*
  // replace numeric entities
  $string = preg_replace('~&#x([0-9a-f]+);~ei', 'chr(hexdec("\\1"))', $string);
  $string = preg_replace('~&#([0-9]+);~e', 'chr("\\1")', $string);
  // replace literal entities
  $trans_tbl = get_html_translation_table(HTML_ENTITIES);
  $trans_tbl = array_flip($trans_tbl);
  return strtr($string, $trans_tbl);
*/
}


/**
 * Implements hook_node_preview()
 * Output customized node preview on node edit and add forms.
 */
function nara_node_preview($variables) {
  $node = $variables['node'];
  $elements = node_view($node, 'full');
  $full = drupal_render($elements);
  $sidebar = render($elements['field_col_b']);
  $output = '';
  $output .= '<div class="row">';
  $output .= '<div class="col-sm-9">' . $full . '</div>';
  $output .= '<div class="col-sm-3">' . $sidebar . '</div>';
  $output .= "</div>";
  return $output;
}

/**
 * Implements hook_preprocess_node
 */
function nara_preprocess_node(&$vars) {
  $node = $vars['node'];

  // Embed the second sidebar in the node so that it can be included
  // within the node template.
  // if ($block = block_get_blocks_by_region('sidebar_second')) {
  //   $vars['sidebar_second'] = $block;
  //   $vars['sidebar_second']['#theme_wrappers'] = array('region');
  //   $vars['sidebar_second']['#region'] = 'sidebar_second';
  // }

  if (isset($node->field_col_b) && isset($node->field_col_b[0]) && isset($node->field_col_b[0]['value']) ) {
    $_SESSION['sidebar_fake'] = render($node->field_col_b[0]['value']);
  }

  // Add template suggestions using view_mode
  if(!empty($vars['view_mode'])) {
    $clean_view_mode = preg_replace('![^abcdefghijklmnopqrstuvwxyz0-9-_]+!s', '', drupal_strtolower($vars['view_mode']));
    $vars['theme_hook_suggestions'][] = 'node__' . $clean_view_mode;
    $vars['theme_hook_suggestions'][] = 'node__' . $node->type . '__' . $clean_view_mode;
  }

  // Add javascript for slideshows
  if($node->type == 'slideshow') {
    drupal_add_js(drupal_get_path('theme', 'archives') . '/js/archives_slideshow.js');
  }

  if($node->type == 'image') {
    if($node->field_image['und'][0]['alt'] == '') {
      $alt = strip_tags($node->body['und'][0]['value']);
      $vars['content']['field_image'][0]['#item']['alt'] = $alt;
    }
  }

  // Add javascript to the locations page
  if (!empty($node->path['alias'])){
    if (!empty($node->path) && $node->path['alias'] == 'locations') {
      // Deprecated??
      // drupal_add_js("http://ecn.dev.virtualearth.net/mapcontrol/mapcontrol.ashx?v=6.2", array('type' => 'external'));
      // drupal_add_js(drupal_get_path('theme', 'archives') . '/include/javascript/locations.js');
      //drupal_add_js("http://ecn.dev.virtualearth.net/mapcontrol/mapcontrol.ashx?v=7.0", array('type' => 'external'));
      drupal_add_js(drupal_get_path('theme', 'nara') . '/include/javascript/locations.js');
    }

    if (!empty($node->path) && $node->path['alias'] == 'contact') {
      // Deprecated??
      // drupal_add_js("http://ecn.dev.virtualearth.net/mapcontrol/mapcontrol.ashx?v=6.2", array('type' => 'external'));
      // drupal_add_js(drupal_get_path('theme', 'archives') . '/include/javascript/locations.js');
      //drupal_add_js("http://ecn.dev.virtualearth.net/mapcontrol/mapcontrol.ashx?v=7.0", array('type' => 'external'));
      drupal_add_js(drupal_get_path('theme', 'nara') . '/scripts/accessible-autocomplete.min.js');
      drupal_add_js(drupal_get_path('theme', 'nara') . '/scripts/contact.js');
    }

    // Add CSS and javascript to the organization page
    if (!empty($node->path) && $node->path['alias'] == 'about/organization' && $vars['page']) {
      drupal_add_css(drupal_get_path('theme', 'archives') . '/include/css/org-charts.css', array('media' => 'screen', 'preprocess' => FALSE, 'weight' => 10));
      drupal_add_css(drupal_get_path('theme', 'archives') . '/include/css/org-charts-print.css', array('media' => 'print', 'preprocess' => FALSE, 'weight' => 10));
      drupal_add_js("DD_roundies.addRule('div.archivist', '8px', true);\nDD_roundies.addRule('div.bluebox', '8px', true);\nDD_roundies.addRule('div.borderbox','8px',true);\nDD_roundies.addRule('div.redbox','8px', true);", array('type'=>'inline', 'weight' => 10, 'group' => JS_THEME));
    }
  }

  // Prepare the exit and display URLs for exit pages and add the javascript
  // needed to redirect the user to the external page
  if ($node->type == 'exit_page') {
    if (isset($_GET['link']) && nara_okay_to_exit()) {
      $exit_link = explode('link=', request_uri());
      $exit_link = $exit_link[1];
      $vars['exit_link'] = $exit_link;
      $display_link = $exit_link;
      if (strlen($display_link) > 60) {
        $display_link = substr($display_link, 0, 60) . ' &hellip; <em class="smaller">(URL truncated)</em>';
      }
      $vars['display_link'] = $display_link;
      $vars['is_okay'] = TRUE;
    }
    else {
      $exit_link = url('<front>', array('absolute' => TRUE));
      $vars['is_okay'] = FALSE;
    }

    drupal_add_js(array('archives' => array('exit_url' => $exit_link, 'okay_to_exit' => $vars['is_okay'])), 'setting');
    drupal_add_js(drupal_get_path('theme', 'archives') . '/js/archives_exit.js');
  }

  // Unset fields that can't be removed in UI?
  if ($node->type == 'main_page') {
    unset($vars['content']['field_page_name']);
    unset($vars['content']['field_sections']);
  }

  // Set link style if == url.
  if ($node->type == 'menu') {
    $dom = new DOMDocument();
    @$dom->loadHTML('<body>' . $vars['content']['body'][0]['#markup'] . '</body>');
    $a = $dom->getElementsByTagName('a');
    foreach($a as $link) {
      if($link->getAttribute('href') == request_uri()) {
        $link->setAttribute('class', 'active');
      }
    }
    $body = $dom->getElementsByTagName('body')->item(0);
    $out = '';
    foreach($body->childNodes as $n) {
      $out .= $n->ownerDocument->saveHTML($n);
    }
    $vars['content']['body'][0]['#markup'] = $out;
  }

  //dd($vars['content']['field_date_of_event']);
  //unset($vars['content']['field_date_of_event']['#items']);

  if(!empty($vars['content']['field_date_of_event']['#items'])){
    $itemcount=count($vars['content']['field_date_of_event']['#items']);

    if($itemcount > 1){
      $wrapper = array(
        '#type' => 'container',
        '#attributes' => array(
          'class' => array('recent-dates'),
        ),
      );
      $wrapper2 = array(
        '#type' => 'container',
        '#attributes' => array(
          'class' => array('more-dates'),
        ),
      );
      for($i=0; $i <= $itemcount;$i++){
        if($i < 3){
          $wrapper[] = $vars['content']['field_date_of_event'][$i];
        }
        else{
          $wrapper2[] = $vars['content']['field_date_of_event'][$i];
        }
        unset($vars['content']['field_date_of_event'][$i]);
      }

      if(!empty($wrapper)){
        $vars['content']['field_date_of_event'][0]=$wrapper;
      }
      if(!empty($wrapper2)){
        $vars['content']['field_date_of_event'][1]=$wrapper2;
      }
      //dd($vars['content']['field_date_of_event']);
    }
  }

  if ($node->type == 'training_course') {
    if(isset($vars['content']['field_display_type'])){

      if(!empty($vars['content']['field_course_video']) && !empty($vars['content']['field_course_material'])){
        $col = 'col-sm-6';
      }
      else{
        $col = 'col-sm-12';
      }
      if($vars['content']['field_display_type']['#items'][0]['value'] == 1){
        $fieldswrapper = array(
        '#type' => 'container',
        '#weight' => 8,
        '#attributes' => array(
          'class' => array('row make-eq training-container'),
        ),
        );
        $videowrapper = array(
          '#type' => 'container',
          '#attributes' => array(
            'class' => array($col . ' noIcon'),
          ),
        );

        $videobody = array(
          '#type' => 'container',
          '#attributes' => array(
            'class' => array('traningresource'),
          ),
        );


        $materialbody = array(
          '#type' => 'container',
          '#attributes' => array(
            'class' => array('traningresource'),
          ),
        );

        $materialwrapper = array(
          '#type' => 'container',
          '#attributes' => array(
            'class' => array($col . ' noIcon'),
          ),
        );



        if(!empty($vars['content']['field_course_material'])){
          $materialbody[] = $vars['content']['field_course_material'];
          $materialwrapper[] = $materialbody;
          $fieldswrapper[] = $materialwrapper;
        }

        if(!empty($vars['content']['field_course_video'])){
          $videobody[] = $vars['content']['field_course_video'];

          $videowrapper[] = $videobody;
          $fieldswrapper[] = $videowrapper;
        }


      }
      elseif ($vars['content']['field_display_type']['#items'][0]['value'] == 2) {
        $fieldswrapper = array(
          '#type' => 'container',
          '#weight' => 8,
          '#attributes' => array(
            'class' => array('noIcon'),
          ),
        );


        $vars['content']['field_course_video']['#label_display'] = hidden;
        $vars['content']['field_course_material']['#label_display'] = hidden;
        $fieldswrapper[] = $vars['content']['field_course_video'];
        $fieldswrapper[] = $vars['content']['field_course_material'];

      }
      $vars['content'][]=$fieldswrapper;
      unset($vars['content']['field_course_video']);
      unset($vars['content']['field_course_material']);
      unset($vars['content']['field_display_type']);
    }
  }


}

/**
 * Implements hook_process_node. Adds the PDF Notice block if a PDF was found
 * in the left menus or in the node body.
 */
function nara_process_node(&$vars) {
/*
  if($vars['page'] && $vars['type'] != 'image') {
    if(!isset($vars['show_pdf'])) {
      if (strpos($vars['body'][0]['value'], '.pdf') !== FALSE) {
        $vars['show_pdf'] = TRUE;
      } elseif(!empty($vars['field_col_b']) && strpos($vars['field_col_b'][0]['value'], '.pdf') !== FALSE) {
        $vars['show_pdf'] = TRUE;
      }
    }
    if(isset($vars['show_pdf']) && $vars['show_pdf']) {
      if($blocks = block_get_blocks_by_region('pdf_notice')) {
        $vars['pdf_notice'] = $blocks;
      }
    }
  }
*/
}

/**
 * Implements hook_preprocess_page
 */
function nara_preprocess_page(&$vars) {

  // Build section header colors.
  $section_info = SectionInfo::instance();
  $primary_color = $section_info->getPrimaryColor();
  $mouseover_color = $section_info->getScrimColor();
  $scrim_color = $section_info->getScrimColor();
  $header_image = $section_info->getHeaderImage();
  $vars['section_class'] = 'section-theme';

  // Adds section header colors for 404 pages.
  $status = drupal_get_http_header("status");
  $section_css = '';
  if ($status !== '404 Not Found') {
    $section_css .= '
      .section-theme #title-bar {
        background: '.$primary_color.' url("'.$header_image.'") no-repeat center right;
    }';
  }
  else {
    $primary_color = '#29393b;';
    $scrim_color = 'rgba(93,131,138,0.5)';
    $mouseover_color = 'lightblue';
    $section_css .= '
      .section-theme #title-bar {
      background: '.$primary_color.';
    }';
  }

  $section_css .= '.section-theme #mega-footer > h2 {background: '.$primary_color.' }';

  $section_css .= '
    .section-theme #title-bar .breadcrumb {
      background-color: '.$scrim_color.';
    }
    .section-theme th {
      background-color: '.$primary_color.' !important;
    }
    /*
    .section-theme .btn-primary {
      background-color: '.$primary_color.';
      border-color: '.$primary_color.';
    }
    .section-theme .btn-primary:focus, .section-theme .btn-primary:hover, .section-theme .btn-primary:active, .section-theme .btn-primary:visited {
      background-color: '.$mouseover_color.';
      color: #fff;
  }*/';
  drupal_add_css($section_css, array('type' => 'inline'));

  // National Archives News & Events
  // If this is a news_event page
  if(isset($vars['node']) && $vars['node']->type == 'news_event'){
    array_unshift($vars['theme_hook_suggestions'],'page__news');
    $section_info->setFooterTitle('About Us');
  }

  // Handle column b for press_release and news
  $render_node = '';
  if (isset($vars['node']) && arg(2) !== 'edit') {
    $render_node = '';
    $node_side = clone $vars['node'];
    $node_side = node_view($node_side);
    unset($node_side['body']);
    unset($node_side['field_nara_freshness']);
    unset($node_side['field_nara_freshness_timer']);
    $render_node = drupal_render($node_side['field_col_b']);

    if( ($vars['node']->type == 'news_event'
      || $vars['node']->type == 'press_release'
      || $vars['node']->type == 'news') ) {
      $vars['page']['sidebar_second']['columnb'] = array('#markup' => $render_node);
    }
  }

  if (isset($vars['node'])
    && arg(2) !== 'edit'
    && ($vars['node']->type == '3_col_page'
    || $vars['node']->type == '2_col_left_page'
    || $vars['node']->type == 'training_course'
    || $vars['node']->type == 'portal_page'
    || $vars['node']->type == 'webform') ){

    if(!empty($render_node) && !empty(preg_replace('~[\r\n]+~', '', $render_node))){
      $vars['page']['sidebar_second']['columnb'] = array('#markup' => $render_node);
    }
  }

  /* block non page rendering and return 404 */
  $nonpage_type=['accordion','accordion_panel',
                 'exhibit','user_alert','homepage_feature',
                 'image','media_gallery','mega_footer','menu',
                 'news','publication',
                 'slide','slideshow','snippet','timeline_page',
                 'timeline_era','timeline_item'
                ];
  if ( isset($vars['node']) && ! user_is_logged_in() && in_array($vars['node']->type,$nonpage_type)  ){
    drupal_not_found();
  }


  if(drupal_get_path_alias($_GET['q']) == 'education' ) {
    $twitter_docs_teach = <<<TWITTER_DOC_TEACH
      <div class="block">
        <a class="twitter-timeline" href="https://twitter.com/DocsTeach" data-widget-id="511847145939279872">Tweets by @DocsTeach</a>
        <script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+"://platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
      </div>
TWITTER_DOC_TEACH;

    $vars['page']['sidebar_second']['views_sidebars-block']['#markup'] = str_replace('[twitter_docs_teach]',$twitter_docs_teach, $vars['page']['sidebar_second']['views_sidebars-block']['#markup']);
    array_unshift($vars['theme_hook_suggestions'], 'page__2_column_right');
  }

  $path = $_GET['q'];
  $path = str_replace('/index', '', $path);
  if (isset($vars['node'])) {
    $node = $vars['node'];
    $path = str_replace('/index', '', drupal_get_path_alias('node/' . $node->nid));
  }

  if (preg_match('/preservation\/products/', $path)) {
    drupal_add_css(drupal_get_path('theme', 'archives') . '/css/products/nwts.css');
  }

  // Creates the classes for each section
  $classes = array();
  $splits = explode('/', $path);
  $separator='';
  $path_separator = '';
  if (sizeof($splits)>1) {
    $value='';
    $parent = '';
    foreach($splits as $class) {
      if ($class == $splits[sizeof($splits)-1]) break;
      $value = $value.$separator.$class;
      $classes[] = $value;
      $parent = $parent.$path_separator.$class;
      $separator = '-';
      $path_separator = '/';
    }
  }
  else {
    $classes[] = $splits[0];
  }
  //drupal_set_breadcrumb($breadcrumb);
  //$vars['breadcrumb'] = $section_info->getBreadcrumb();
  //$breadcrumb_path = $section_info->getBreadcrumbPath();

  // Set the section title and link
  $section_link = $section_info->getSectionLink();
  $section_title = $section_info->getSectionName();
  $vars['section_link'] = l($section_title, $section_link, array('html' => TRUE));
  $vars['section_title'] = $section_title;

  // Set the footer
  $vars['page']['footer']['mega-footer'] = $section_info->getFooter();
  $vars['page']['below-footer']['#prefix'] = '';
  $vars['page']['below-footer']['#suffix'] = '';

  // Add the footer menus and side menus, if used
  if (isset($node)) {
    $hide_left_menu = false;
    if (isset($node->field_hide_left_menu['und'][0])) {
      $hide_left_menu = $node->field_hide_left_menu['und'][0]['value'];

    }
    $arg2 = arg(2);
    if ((empty($arg2) || (!in_array($arg2, array('edit','clone')))) && in_array($node->type, array('2_col_page', '3_col_page', 'training_course', 'main_page', 'press_release', 'webform','news_event','event','news'))) {
      $menus = $section_info->getMenus();
      if(!empty($menus) && ($hide_left_menu != true)) {
        foreach($menus as $key => $menu) {
          $vars['page']['sidebar_first']['left-menu-' . $key] = $menu;
        }
      }
      if((!empty($menus)  && ($hide_left_menu != true)) && !empty($vars['page']['sidebar_second'])) {
        $vars['content_column_class'] = ' class="col-sm-6"';
      }
      if((!empty($menus)  && ($hide_left_menu != true)) && empty($vars['page']['sidebar_second'])) {
        $vars['content_column_class'] = ' class="col-sm-9"';
      }
    }
    if((empty($arg2) || !in_array($arg2, array('edit','clone'))) && in_array($node->type, array('page', '2_col_page', '2_col_left_page', '3_col_page', 'training_course', 'main_page', 'portal_page', 'app_page', 'blank_page', 'historical_doc', 'press_release', 'exit_page'))) {
      $vars['theme_hook_suggestions'][] = 'page__no_wrapper';
    }

    $vars['theme_hook_suggestions'][] = 'page__' . $vars['node']->type;

    // Reuse the Application Page page template for the full view of images
    if($node->type == 'image') {
      $vars['theme_hook_suggestions'][] = 'page__app_page';
    }

    // Reuse the Blank Page template for exit pages
    if($node->type == 'exit_page') {
      $vars['theme_hook_suggestions'][] = 'page__blank_page';
    }

  }
  else {
    if(arg(0) == 'news'){
      $menus = $section_info->getMenus();
      if(!empty($menus)) {
        foreach($menus as $key => $menu) {
          $vars['page']['sidebar_first']['left-menu-' . $key] = $menu;
        }
      }
    }

    // Display Views pages like node pages
    $menu_item = menu_get_item();
    if($menu_item['page_callback'] == 'views_page') {
      $node_class = '';
      $vars['theme_hook_suggestions'][] = 'page__3_col_page';
      if(!empty($menu_item['page_arguments'][1])) {
        if($menu_item['page_arguments'][1] == 'press_release_archive') {
          $node_class = 'three-col-page';
        } elseif($menu_item['page_arguments'][1] == 'todays_doc_page') {
          $node_class = 'two-col-left-page';
        } else {
          $node_class = 'two-col-page';
        }
      }
      $classes[] = $node_class;

      if($node_class != 'two-col-left-page') {
        // don't want menu in 'not-todays' doc
        if($menu_item['page_arguments'][1] != 'other_days_docs') {
          $menus = $section_info->getMenus();
          foreach($menus as $key=>$menu) {
            $vars['page']['sidebar_first']['left-menu-' . $key] = $menu;
            if(strpos($menu['body'][0]['#markup'], '.pdf') !== FALSE) {
              $vars['node']->pdf_notice = TRUE;
            }
          }
        }
      }
    }
  }

  $vars['container_classes'] = implode(' ', $classes);

  // Add the footer menu for the front page as well
  if($vars['is_front'] || empty($footer)) {
    $footer = taxonomyhandler_find_footer();

    if(empty($vars['page']['footer']['mega-footer'])) {
      $vars['page']['footer']['mega-footer'] = $footer;
    }
  }


  // If this is a news_event page
  if ((isset($vars['node']) && $vars['node']->type == 'news_event') ) { //|| arg(0) == 'news'){
    drupal_set_title('National Archives News');
  }
  $breadcrumb = drupal_get_breadcrumb();
    
  if(isset($breadcrumb[1]) && $breadcrumb[1] == '<a href="/findingaid/stat/discovery">Discovery</a>'){
    $breadcrumb[1] = "<a href='/findingaid/explorer/'>Record Group Explorer</a>";
  }
  elseif(isset($breadcrumb[1]) && $breadcrumb[1] == '<a href="/pl-explorer/list">List</a>'){
    $breadcrumb[1] = "<a href='/pl-explorer/explorer'>Presidential Library Explorer</a>";
  }
  
  $vars['breadcrumb'] =theme('breadcrumb', array('breadcrumb' => $breadcrumb));


  //dd(array_keys($vars['page']['content']));



  if( isset($vars['page']['content']['system_main']['#form_id']) &&
      ($vars['page']['content']['system_main']['#form_id'] == 'user_pass' || $vars['page']['content']['system_main']['#form_id'] == 'user_login')
    ){

    //dd($vars['page']['content']['system_main']);
    $vars['page']['content']['system_main']['notice'] = array(
      '#weight'=>100,
      '#markup'=>'<p>Use your NARANet login.  Visit <a href="https://pss.archives.gov">pss.archives.gov</a> to reset your NARANet password and Drupal password.</p>'
    );
  }

} // func PAGE


/**
 * Implements hook_preprocess_html
 */
function nara_preprocess_html(&$vars) {

  // add a section wrapper class depending on PATHs used for site section styling, e.g., research, veterans, education, etc.
  $path_alias = strtolower(preg_replace('/[^a-zA-Z0-9-]+/', ' ', drupal_get_path_alias($_GET['q'])));
  $section = explode(" ", $path_alias);
  if ($path_alias == 'node') {
    // $vars['section_class'] = ' ';
  }
  else {
    // we just want the first item in the path_alias string
    // $vars['section_class'] = 'section-'. $section[0];
  }
  $vars['html_head_bottom'] = block_get_blocks_by_region('html_head_bottom');
  $vars['html_body_top'] = block_get_blocks_by_region('html_body_top');

}

/**
 * Implements hook_node_view_alter()
 */
function nara_node_view_alter(&$build) {
  // Remove Drupal's read more link
  if($build['#view_mode'] == 'teaser') {
    if (isset($build['links']) && isset($build['links']['node']) && isset($build['links']['node']['#links']))
      unset($build['links']['node']['#links']['node-readmore']);
  }
}

/**
 * Theming function for the menu on the front page
 */
function nara_links__front_page_menu($variables) {
  $links = $variables['links'];
  $attributes = $variables['attributes'];
  $heading = $variables['heading'];
  $output = array();

  if (count($links) > 0) {
    // Treat the heading first if it is present to prepend it to the
    // list of links.
    if (!empty($heading)) {
      if (is_string($heading)) {
        // Prepare the array that will be used when the passed heading
        // is a string.
        $heading = array( 'text' => $heading, 'level' => 'h2', );
      }
      $h = '<' . $heading['level'];
      if (!empty($heading['class'])) {
        $h .= drupal_attributes(array('class' => $heading['class']));
      }
      $h .= '>' . check_plain($heading['text']) . '</' . $heading['level'] . '>';
      $output[] = $h;
    }

    $output[] = '<ul' . drupal_attributes($attributes) . '>';

    foreach ($links as $key => $link) {
      $class = array($key);
      if(!empty($link['attributes']['id'])) {
        $attributes['id'] = $link['attributes']['id'];
        unset($link['attributes']['id']);
      }
      $attributes['class'] = $class;
      $output[] = '<li' . drupal_attributes($attributes) . '>';

      if (isset($link['href'])) {
        // Output the link description as the subtitle
        if(!empty($link['attributes']['title'])) {
          $title = $link['title'];
          $subtitle = $link['attributes']['title'];
          unset($link['attributes']['title']);
          $text = '<span>' . check_plain($title) . '</span>' . check_plain($subtitle);
          $options = $link;
          $options['html'] = TRUE;
          $output[] = l($text, $link['href'], $options);
        } else {
          $output[] = l($link['title'], $link['href'], $link);
        }

      }
      elseif (!empty($link['title'])) {
        // Some links are actually not links, but we wrap these in <span> for adding title and class attributes.
        if (empty($link['html'])) {
          $link['title'] = check_plain($link['title']);
        }
        $span_attributes = '';
        if (isset($link['attributes'])) {
          $span_attributes = drupal_attributes($link['attributes']);
        }
        $output[] = '<span' . $span_attributes . '>' . $link['title'] . '</span>';
      }
      $output[] = "</li>";
    }
    $output[] = '</ul>';
  }
  return implode('', $output);
}

/**
 * Theming function for the footer menu
 */
function nara_menu_tree__menu_footer_menu(&$variables) {
  return '<ul id="footerMicroButtons">' . $variables['tree'] . '</ul>';
}

/**
 * Return a themed breadcrumb trail.
 *
 * @param $vars
 *
 * @internal param $breadcrumb An array containing the breadcrumb links.
 *
 * @return string A string containing the breadcrumb output.
 */
function nara_breadcrumb($vars) {

  // Do not modify breadcrumbs if the Path Breadcrumbs module should be used.
  $breadcrumbs = &$vars['breadcrumb'];
  if ($node = menu_get_object() ) {
    if(!empty($node->field_breadcrumb) && !empty($node->field_breadcrumb['und'][0]['safe_value'])){
      end($breadcrumbs);
      $key = key($breadcrumbs);
      reset($breadcrumbs);
      $breadcrumbs[$key]['data']=$node->field_breadcrumb['und'][0]['safe_value'];
    }
  }

  // Optionally get rid of the homepage link.
  $output = "";
  foreach($breadcrumbs as $key => $crumb) {
    if(is_array($crumb)){
      #$output .= "<span class=\"".implode(' ',$crumb['class'])."\">&gt; ".$crumb['data']."</span>";
      $output .= "&gt; ". $crumb['data'];
    }else{
      if($key > 0) {
        $output .= "&gt; $crumb ";
      }else{
        $output .= "$crumb ";
      }
    }
  }

  return $output;
}

function nara_breadcrumb_custom($vars) {

  $breadcrumbs = $vars['breadcrumb'];
  //get rid of the last item which is added automatically for the active page
  //we'll set it ourselves when calling the themehook

  if(empty($vars['breadcrumb']))
      return '';

  $output = "";
  $output .= '<ol class="breadcrumb">';
  foreach($breadcrumbs as $crumb) {
      $output .= "<li>$crumb</li> ";
  }
  $output .= '</ol>';


  return $output;
}

/**
 * Output a list of anchor links for the months of a given fiscal year
 *
 * @param string $year
 *
 * @return string
 */
function nara_monthly_lists($year) {
  $months = array(
    'sep' => t('September'),
    'aug' => t('August'),
    'jul' => t('July'),
    'june' => t('June'),
    'may' => t('May'),
    'apr' => t('April'),
    'mar' => t('March'),
    'feb' => t('February'),
    'jan' => t('January'),
    'dec' => t('December'),
    'nov' => t('November'),
    'oct' => t('October'),
  );
  $output = array();
  $i = 1;
  foreach($months as $short => $long) {
    $link_text = $long;
    if($i > 9) {
      $link_text .= ' (' . $year - 1 . ')';
    }
    $options = array('fragment' => $short . substr($year, 2));
    $output[] = l($link_text, 'press/press-releases/date-archive.html', $options);
    $i++;
  }
  return implode('', $output);
}

function nara_views_pre_render(&$view) {
  if ($view->name === 'press_releases') {
    //krumo($view->result);
  }
}


function nara_views_view_fields__press_releases__years_only($vars) {
  // krumo($vars);
  $fields = $vars['fields'];
  if(!empty($fields['field_fiscal_year'])) {
    $year = $fields['field_fiscal_year']->content;
    return '<a href="/press/press-releases/' . $year . '">' . $year . '</a>';
  }
  return '';
}

/*** Theming functions for fields ***/

/**
 * Default theming function for fields; display content with minimal wrappers
 * @param array $vars
 * @return string
 */
function nara_field($vars) {
  $output = array();
  if(!$vars['label_hidden']) {
    $output[] = '<span class="field-label"' . $vars['title_attributes'] . '>' . $vars['label'] . '</span>';
  }
  foreach($vars['items'] as $item) {
    $output[] = render($item);
  }
  return implode('', $output);
}

function nara_field__field_publisher_email($vars) {
  $output = array();
  $output[] = '<div class="profile-field">';
  if(!$vars['label_hidden']) {
    $output[] = '<span class="field-label"' . $vars['title_attributes'] . '>' . $vars['label'] . ':</span>';
  }
  foreach($vars['items'] as $item) {
    $output[] = '<span class="field-item">' . render($item) . '</span>';
  }
  $output[] = '</div>';
  return implode('', $output);
}

function nara_field__field_editor_email($vars) {
  return nara_field__field_publisher_email($vars);
}

function nara_field__field_creation_path($vars) {
  $output = array();
  $output[] = '<div class="profile-field">';
  if(!$vars['label_hidden']) {
    $output[] = '<span class="field-label"' . $vars['title_attributes'] . '>' . $vars['label'] . ':</span>';
  }
  $output[] = '<span class="field-items">';
  foreach($vars['items'] as $item) {
    $section = render($item);
    $output[] = '<span class="field-item">' . l($section, $section) . '</span>';
  }
  $output[] = '</span>';
  $output[] = '</div>';
  //return implode('',$output);
  return '';
}

function nara_field__field_onlineurl($vars) {
  $item = array_shift($vars['items']);
  if($item) {
    $options = array('attributes' => array('class' => array('view-online')), 'html' => TRUE);
    return l(
      '<img src="/global-images/icons/24x24/browse-web.gif" align="absmiddle" /> View Online',
      render($item),
      $options
    );
  }
  return FALSE;
}

function nara_field__field_href($vars) {
  $item = array_shift($vars['items']);
  if($item) {
    return l('More Info', render($item), array('attributes' => array('class' => array('moreLink'))));
  }
  return FALSE;
}

function nara_field__field_links_more($vars) {
  $output = array();
  $output[] = '<div class="titleBox">';
  $output[] = '<h3 class="titleBar4">' . t('Read More') . '</h3>';

  foreach($vars['items'] as $item) {
    $output[] = render($item);
  }
  $output[] = '</div>';
  return implode('',$output);
}

function nara_field__field_links_ed($vars) {
  $output = array();
  $output[] = '<div class="titleBox">';
  $output[] = '<h3 class="titleBar6">' . t('Classroom Resources') . '</h3>';

  foreach($vars['items'] as $item) {
    $content = render($item);
    if (strpos($content, '<ul>') === 0) {
      $content = str_replace(array('<ul>', '</ul>'), '', $content);
    }
    if (strpos($content, '<li>') === 0) {
      $output[] = '<ul class="lines">';

      $output[] = $content;
      $output[] = '<li>' . l('Tips on Teaching With Documents', 'education/lessons',
          array('attributes' => array('class' => 'faqs-green'))) . '</li>';
      $output[] = '</ul>';
    } else {
      $output[] = $content;
    }
  }
  $output[] = '</div>';
  return implode('', $output);
}

function nara_field__field_links_research($vars) {
  $output = '<div class="titleBox">';
  $output .= '<h3 class="titleBar3">' . t('Research Links') . '</h3>';
  foreach($vars['items'] as $item) {
    $content = render($item);
    if (strpos($content, '<ul>') === 0) {
      $content = str_replace(array('<ul>', '</ul>'), '', $content);
    }
    if (strpos($content, '<li>') === 0) {
      $output .= '<ul class="lines">';
      $content = str_replace(array('<ul>', '</ul>'), '', $content);
      $output .= $content;
      $output .= '<li>' . l('Getting Started with Research', 'research/start', array('attributes' => array('class' => 'start-task'))) . '</li>';
      $output .= '</ul>';
    } else {
      $output .= $content;
    }
  }
  $output .= '</div>';
  return $output;
}

function nara_field__field_slides($vars) {
  $output = array();
  $output[] = '<div class="slideshow-wrapper">';
  $output[] = '<ul class="slideshow" id="slideshow" aria-live="assertive">';
  foreach($vars['items'] as $item) {
    $output[] = '<li>' . render($item) . '</li>';
  }
  $output[] = '</ul></div>';
  return implode('',$output);
}

function nara_views_view_field__press_releases__by_date__field_fiscal_year($vars) {
  $year = strip_tags($vars['output']);
  if(!empty($year)) {
    $last_year = $year - 1;
    $output = l("Fiscal Year {$year}", "press/press-releases/{$year}");
    $output .= "<br/>(October {$last_year} &ndash; September {$year}";
    return $output;
  }
  return FALSE;
}

function nara_views_view_grouping__press_releases__date_archive($vars) {
  // krumo($vars);
  $title = $vars['title'];
  $content = $vars['content'];

  $output = '<div class="view-grouping">';
  $output .= '<h2><a name="fy' . $title . '" id="fy' . $title . '"></a> Fiscal Year ' . $title . '</h2>';
  $output .= '<div id="pr' . $title . '" class="view-grouping-content">' . $content . '</div>' ;
  $output .= '</div>';

  return $output;
}

function nara_menu_local_task($variables) {
  $link = $variables['element']['#link'];
  $options = $link['localized_options'] + array('html' => TRUE);

  $link_text = '<span class="tab">' . $link['title'] . '</span>';

  if (!empty($variables['element']['#active'])) {
    // Add text to indicate active tab for non-visual users.
    $active = '<span class="element-invisible">' . t('(active tab)') . '</span>';

    // If the link does not contain HTML already, check_plain() it now.
    // After we set 'html'=TRUE the link will not be sanitized by l().
    if (empty($link['localized_options']['html'])) {
      $link['title'] = check_plain($link['title']);
    }
    $link['localized_options']['html'] = TRUE;
    $link_text = t('!local-task-title!active', array('!local-task-title' => $link_text, '!active' => $active));
  }
  $out = array();
  $out[] = '<li' . (!empty($variables['element']['#active']) ? ' class="active"' : '') . '>';
  $out[] = l($link_text, $link['href'], $options);
  $out[] = '</li>';
  return implode("\n", $out);
}

function nara_menu_link(array $variables) {
  $element = $variables['element'];
  $sub_menu = '';
  $name_id = strtolower(strip_tags($element['#title']));
  // remove colons and anything past colons
  if (strpos($name_id, ':')) $name_id = substr ($name_id, 0, strpos($name_id, ':'));
  // Preserve alphanumerics, everything else goes away
  $pattern = '/[^a-z]+/ ';
  $name_id = preg_replace($pattern, '', $name_id);
  $element['#attributes']['class'][] = 'menunum-' . $element['#original_link']['mlid'] . ' '.$name_id;
  if ($element['#below']) {
      $sub_menu = drupal_render($element['#below']);
  }
  $output = l($element['#title'], $element['#href'], $element['#localized_options']);
  return '<li' . drupal_attributes($element['#attributes']) . '>' . $output . $sub_menu . "</li>\n";
}

function nara_links__system_main_menu($variables) {

    $links = $variables['links'];
    $attributes = $variables['attributes'];
    $heading = $variables['heading'];
    global $language_url;
    $output = '';

    if (count($links) > 0) {
        // Treat the heading first if it is present to prepend it to the
        // list of links.
        if (!empty($heading)) {
            if (is_string($heading)) {
                // Prepare the array that will be used when the passed heading
                // is a string.
                $heading = array(
                    'text' => $heading,

                    // Set the default level of the heading.
                    'level' => 'h2',
                );
            }
            $output .= '<' . $heading['level'];
            if (!empty($heading['class'])) {
                $output .= drupal_attributes(array('class' => $heading['class']));
            }
            $output .= '>' . check_plain($heading['text']) . '</' . $heading['level'] . '>';
        }

        $output .= '<ul' . drupal_attributes($attributes) . '>';

        $num_links = count($links);
        $i = 1;

        foreach ($links as $key => $link) {

            $class = array($key);

            // Add first, last and active classes to the list of links to help out themers.
            if ($i == 1) {
                $class[] = 'first';
            }
            if ($i == $num_links) {
                $class[] = 'last';
            }
            if (isset($link['href']) && ($link['href'] == $_GET['q'] || ($link['href'] == '<front>' && drupal_is_front_page())) && (empty($link['language']) || $link['language']->language == $language_url->language)) {
                $class[] = 'active';
            }

            $class[] = $link['attributes']['id'];

            $output .= '<li' . drupal_attributes(array('class' => $class)) . '>';

            if (isset($link['href'])) {
                // Pass in $link as $options, they share the same keys.
                $output .= l($link['title'], $link['href'], $link);
            }
            elseif (!empty($link['title'])) {
                // Some links are actually not links, but we wrap these in <span> for adding title and class attributes.
                if (empty($link['html'])) {
                    $link['title'] = check_plain($link['title']);
                }
                $span_attributes = '';
                if (isset($link['attributes'])) {
                    $span_attributes = drupal_attributes($link['attributes']);
                }
                $output .= '<span' . $span_attributes . '>' . $link['title'] . '</span>';
            }

            $i++;
            $output .= "</li>\n";
        }

        $output .= '</ul>';
    }

    return $output;
}

/**
 *
 * handles prev and next node links on the news page
 *
 * @param $node
 * @param string $mode
 * @return string
 */
function prev_next_node($node, $mode = 'next') {

    if (!function_exists('prev_next_nid')) {
        return NULL;
    }

    switch($mode) {
        case 'prev':
            $n_nid = prev_next_nid($node->nid, 'prev');

            break;

        case 'next':
            $n_nid = prev_next_nid($node->nid, 'next');
            $link_text = 'next';
            break;

        default:
            return NULL;
    }

    if ($n_nid) {
        $path = drupal_get_path_alias('node/'.$n_nid);
    }
    return $path;
}

/*
 *  Remove paragraph tags around divs generated by shortcodes in the wysiwyg
 */
function nara_clean_p_tags($text){
        $patterns = array(
    '|#!#|is',
    '!<p>(&nbsp;|\s)*(<\/*div>)!is',
    '!<p>(&nbsp;|\s)*(<div)!is',
    '!(<\/div.*?>)\s*</p>!is',
    '!(<div.*?>)\s*</p>!is',
  );

  $replacements = array('', '\\2', '\\2', '\\1', '\\1');
  return preg_replace($patterns, $replacements, $text);
}

function nara_date_nav_title($params) {
  $granularity = $params['granularity'];
  $view = $params['view'];
  $date_info = $view->date_info;
  $link = !empty($params['link']) ? $params['link'] : FALSE;
  $format = !empty($params['format']) ? $params['format'] : NULL;
  switch ($granularity) {
    case 'year':
      $title = $date_info->year;
      $date_arg = $date_info->year;
      break;
    case 'month':
      $format = !empty($format) ? $format : (empty($date_info->mini) ? 'M Y' : 'M Y');
      $title = date_format_date($date_info->min_date, 'custom', $format);
      $date_arg = $date_info->year .'-'. date_pad($date_info->month);
      break;
    case 'day':
      $format = !empty($format) ? $format : (empty($date_info->mini) ? 'l, F j Y' : 'l, F j');
      $title = date_format_date($date_info->min_date, 'custom', $format);
      $date_arg = $date_info->year .'-'. date_pad($date_info->month) .'-'. date_pad($date_info->day);
      break;
    case 'week':
        $format = !empty($format) ? $format : (empty($date_info->mini) ? 'F j Y' : 'F j');
      $title = t('Week of @date', array('@date' => date_format_date($date_info->min_date, 'custom', $format)));
        $date_arg = $date_info->year .'-W'. date_pad($date_info->week);
        break;
  }
  if (!empty($date_info->mini) || $link) {
      // Month navigation titles are used as links in the mini view.
    $attributes = array('title' => t('View full page month'));
      $url = date_pager_url($view, $granularity, $date_arg, TRUE);
    return l($title, $url, array('attributes' => $attributes));
  }
  else {
    return $title;
  }
}
/*
 * theme fix to address w3c validation issue
 * https://www.drupal.org/node/2514028
 */
function nara_button($variables) {
    $element = $variables['element'];
      $element['#attributes']['type'] = 'submit';
      $attributes = array('id', 'value');
        if (!empty($element['#name'])) {
              $attributes[] = 'name';
        }
        else{


        }
      element_set_attributes($element, $attributes);
      $element['#attributes']['class'][] = 'form-' . $element['#button_type'];
  if (!empty($element['#attributes']['disabled'])) {
        $element['#attributes']['class'][] = 'form-button-disabled';
  }

  return '<input' . drupal_attributes($element['#attributes']) . ' />';
}

/* custom form theming for 508 & html5 validation */
/*
function nara_form_element($variables) {
  $element = &$variables['element'];

  // This function is invoked as theme wrapper, but the rendered form element
  // may not necessarily have been processed by form_builder().
  $element += array(
    '#title_display' => 'before',
  );

  // Add element #id for #type 'item'.
  if (isset($element['#markup']) && !empty($element['#id'])) {
    $attributes['id'] = $element['#id'];
  }
  // Add element's #type and #name as class to aid with JS/CSS selectors.
  $attributes['class'] = array('form-item');
  if (!empty($element['#type'])) {
    $attributes['class'][] = 'form-type-' . strtr($element['#type'], '_', '-');
  }
  if (!empty($element['#name'])) {
    $attributes['class'][] = 'form-item-' . strtr($element['#name'], array(' ' => '-', '_' => '-', '[' => '-', ']' => ''));
  }
  // Add a class for disabled elements to facilitate cross-browser styling.
  if (!empty($element['#attributes']['disabled'])) {
    $attributes['class'][] = 'form-disabled';
  }
  $output = '<div' . drupal_attributes($attributes) . '>' . "\n";

  // If #title is not set, we don't display any label or required marker.
  if (!isset($element['#title'])) {
    $element['#title_display'] = 'none';
  }
  $prefix = isset($element['#field_prefix']) ? '<span class="field-prefix">' . $element['#field_prefix'] . '</span> ' : '';
  $suffix = isset($element['#field_suffix']) ? ' <span class="field-suffix">' . $element['#field_suffix'] . '</span>' : '';
/*
  debug($element,'element',true);
  if ($element['#type'] == 'radios') {
    debug($element['#description'],'description');
  }
  if ($element['#type'] == 'radio') {
    debug($element,'radio',true);
#    $element['#title_display'] = 'none';
#    $variables['rendered_element'] = ' ' . $prefix . $element['#children'] . $suffix . "\n";
    $output .= theme('form_element_label', $variables);
#    $output .= ' ' . $prefix . $element['#children'] . $suffix . "\n";
#    $output .= $variables['rendered_element'] ;
  }
  else {
   switch ($element['#title_display']) {
    case 'before':
    case 'invisible':
      $output .= ' ' . theme('form_element_label', $variables);
      $output .= ' ' . $prefix . $element['#children'] . $suffix . "\n";
      break;

    case 'after':
      $output .= ' ' . $prefix . $element['#children'] . $suffix;
      $output .= ' ' . theme('form_element_label', $variables) . "\n";
      break;

    case 'none':
    case 'attribute':
      // Output no label and no required marker, only the children.
      $output .= ' ' . $prefix . $element['#children'] . $suffix . "\n";
      break;
   }
  #}
  if (!empty($element['#description'])) {
    $output .= '<div class="description">' . $element['#description'] . "</div>\n";
  }

  $output .= "</div>\n";

  return $output;
}
 */

