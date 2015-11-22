<?php
session_start();
require_once('engine/dibi/loader.php');
dibi::connect(
  array(
    'driver'   => 'mysqli',
    'host'     => '127.0.0.1',
    'username' => 'root',
    'password' => '',
    'database' => 'gitlist',
    'charset'  => 'utf8',
  )
);

function page_list(){
  $out = '<ul>';
  $out .= '<li><a href="index.php?action=userlist">User repositories list</a>';
  $out .= '<li><a href="index.php?action=searchlist">Search history list</a>';
  $out .= '<li><a href="index.php?action=searchclear">Search history clear</a>';
  $out .= '</ul>';
  return $out;
}

function user_list($get, $post){
  $username = $post['user'];
  if(!empty($username)){
    $title = 'Github repositories list for user "'.$username.'"';
    $reps = github_api_curl('https://api.github.com/users/'.$username.'/repos?sort=created');//sort=created - sort by repository creation date - default order DESC
    $replist = json_decode($reps, true);
    dibi::query('INSERT INTO search_history (time, ip, query) VALUE (NOW(), %s, %s)', $_SERVER['REMOTE_ADDR'], $username);//save to history
    $list .= '<table>';
    foreach($replist as $rep){
      $list .= '<tr>';
      $list .= '<td>'.$rep['created_at'].'</td>';
      $list .= '<td>'.$rep['name'].'</td>';
      $list .= '<td><pre>'.var_export($rep, true).'</pre></td>';
      $list .= '</tr>';
    }
    $list .= '</table>';
  }
  else {
    $title = 'Github repositories list';
    $list = '';
  }
  $out .= '<h1>'.$title.'</h1>';
  $out .= '<form method="post" action="index.php?action=userlist"><div>';
  $out .= 'User name: <input type="text" name="user" value="'.$username.'" /> ';
  $out .= '<input type="submit" value="Submit" />';
  $out .= '</div></form>';
  $out .= $list;
  return $out;
}

function github_api_curl($url){
  $options = array(
    CURLOPT_RETURNTRANSFER => true,     // return web page
    CURLOPT_HEADER         => false,    // don't return headers
    CURLOPT_FOLLOWLOCATION => true,     // follow redirects
    CURLOPT_ENCODING       => "",       // handle all encodings
    CURLOPT_USERAGENT      => "spider", // who am i
    CURLOPT_AUTOREFERER    => true,     // set referer on redirect
    CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
    CURLOPT_TIMEOUT        => 120,      // timeout on response
    CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
    CURLOPT_SSL_VERIFYPEER => false     // Disabled SSL Cert checks - not optimal solution, but for this purpose acceptable
  );

  $ch = curl_init($url);
  curl_setopt_array($ch, $options);
  $content = curl_exec($ch);
  $err = curl_errno($ch);
  $errmsg = curl_error($ch);
  $header = curl_getinfo($ch);
  curl_close( $ch );
  return $content;
  /* //return complete info with errors
  $header['errno']   = $err;
  $header['errmsg']  = $errmsg;
  $header['content'] = $content;
  return $header;
  */
}


function search_list($get){
  $out .= '<h1>Search query history</h1>';
  if(empty($get['page'])) $get['page'] = 1;
  $lmt = 10;//number of rows per page
  $ofs = $lmt * ($get['page'] - 1);
  $count = dibi::fetchSingle('SELECT count(*) FROM search_history');
  $pagecount = ceil($count / $lmt);
  $searches = dibi::query('SELECT * FROM search_history WHERE f_deleted=0 ORDER BY time DESC %lmt %ofs', $lmt, $ofs);
  for($i = 1; $i <= $pagecount; $i++){
    $out .= '<a href="index.php?action=searchlist&page='.$i.'">'.$i.'</a>&nbsp; ';
  }
  $out .= '<table>';
  foreach($searches as $search){
    $out .= '<tr>';
    $out .= '<td>'.$search['time'].'</td>';
    $out .= '<td>'.$search['ip'].'</td>';
    $out .= '<td>'.$search['query'].'</td>';
    $out .= '</tr>';
  }
  $out .= '</table>';
  return $out;
}

function search_clear_form($get){
  if(!is_logged()) return login_form('searchclear');
  $out .= '<h1>Clear search history</h1>';
  $out .= '<form method="post" action="index.php?action=searchlist"><div>';
  $out .= '<input type="hidden" name="action" value="searchclear" />';
  $out .= 'Remove items older than: <input type="number" name="hour" value="0" min="0" /> hours';
  $out .= '<br /><br />';
  $out .= '<input type="submit" value="Submit" />';
  $out .= '</div></form>';
  return $out;
}

function searchclear($post){
  if(!is_logged()) return false;//unauthorised request
  dibi::query('UPDATE search_history SET f_deleted=1 WHERE time < %t', '-'.$post['hour'].' hours');
}

function login_form($action){
  $out .= '<h1>Login</h1>';
  $out .= 'Authentification required, please log in.<br /><br />';
  $out .= '<form method="post" action="index.php?action='.$action.'"><div>';
  $out .= '<input type="hidden" name="action" value="dologin" />';
  $out .= 'User: <input type="text" name="user" value="" />';
  $out .= ' Password: <input type="password" name="pass" value="" />';
  $out .= '<br /><br />';
  $out .= '<input type="submit" value="Submit" />';
  $out .= '</div></form>';
  return $out;
}

function dologin($username, $password){
  if($username == 'user' && $password == 'password'){
    $_SESSION['logged'] = true;
  }
}

function is_logged(){
  return $_SESSION['logged'] == true;
}
////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
echo '<html>
<head>
  <title>Github repositories list</title>
  <style>
    table{border-spacing: 0;border-collapse:collapse; }
    td{border:1px solid #333333;vertical-align:top;width: 100%;}
    pre{max-height: 150px; overflow:auto;}
  </style>
</head>
<body>
<a href="index.php">Home</a><br />';
switch($_POST['action']){
  case 'dologin':
    dologin($_POST['user'], $_POST['pass']);
    break;
  case 'searchclear':
    searchclear($_POST);
    break;
  default:break;
}
switch($_GET['action']){
  case 'userlist':
    echo user_list($_GET, $_POST);
    break;
  case 'searchlist':
    echo search_list($_GET);
    break;
  case 'searchclear':
    echo search_clear_form($_GET);
    break;
  case 'pagelist':default:
    echo page_list();
}
echo '</body>';
?>