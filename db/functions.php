<?php
 // $date = date('D, dS F Y @ H:i:s A');
///////////////DACOMSOTAL ///////////////////
 ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);



function QueryDB($sql, $params = []) {
  global $pdo; // assumes $pdo is your PDO connection

  if (!empty($params)) {
      $stmt = $pdo->prepare($sql);
      $stmt->execute($params);
      return $stmt;
  } else {
      return $pdo->query($sql);
  }
}

function get_code(){

  global $conn;
  $alphabets ="ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvvxyz";
  $shuffled = str_shuffle($alphabets);
  $serials = substr($shuffled, 0,3).rand(100,999);
  $final = str_shuffle($serials);
  return $final;
  
}

function d_code(){

  global $conn;
  $alphabets ="ABCDEFGHJKLMNPQRSTUVWXYZ23456789";
  $shuffled = str_shuffle($alphabets);
  $serials = substr($shuffled, 0,5).rand(100,999);
  $final = str_shuffle($serials);
  return $final; 
}

function ans_code(){

  global $conn;
  $alphabets ="ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvvxyz";
  $shuffled = str_shuffle($alphabets);
  $serials = substr($shuffled, 0,2).rand(100,999);
  $final = str_shuffle($serials);
  return $final;
}

function author_code(){

  global $conn;
  
  $alphabets ="ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvvxyz";
  
  $shuffled = str_shuffle($alphabets);
  
  $serials = substr($shuffled, 0,5).rand(100,999);
  
  return $serials;
  
}

function validate($value){
  $value = trim($value);
  $value = stripslashes($value);
  $value = htmlspecialchars($value);
  $value = str_replace('"','&quot;', $value);
  $value = str_replace("'",'&apos;', $value);
  return $value;
}

function linker($value){
  $value = validate($value);
  $value = str_replace (' ','_',$value);
  $value = str_replace (" ","_",$value);
  $value = str_replace ("-","_",$value);
  $value = str_replace (".","_",$value);
  $value = str_replace ("/","_",$value);
  $value = str_replace ("'","",$value);
  $value = str_replace ("&","",$value);
  $value = str_replace ("&apos;","",$value);
  $value = str_replace ("&quot;","",$value);
  $value = strtolower ($value);
  return $value;

}

function code_pics($count){
  $alphabets ='ABCDEFGHIJKLMNOPQRSTUVWXYZ'; $rCode = rand(10,99);
  $class_unique = rand(10,99).substr(str_shuffle($alphabets),0,$count).$rCode;
  return $class_unique;
}

function _greetin(){
  date_default_timezone_set('Africa/lagos');



// 24-hour format of an hour without leading zeros (0 through 23)

  $Hour = date('G');
  if ( $Hour >= 1 && $Hour <= 11 ) {
    $salute = 'Good Morning   ';

  } else if ( $Hour >= 12 && $Hour <= 16 ) {
    $salute = 'Good Afternoon  ';

  } else if ( $Hour >= 17 || $Hour <= 22 ) {
    $salute = 'Good Evening   ';
  }
  else if ( $Hour >= 23 || $Hour <= 24 ) {
    $salute = 'Keeping Late Night?   ';
  }
  return $salute;

}

function get_time_ago( $time )

{

  $time_difference = time() - $time;

  if( $time_difference < 1 ) { return 'less than 1 second ago'; }

  $condition = array( 12 * 30 * 24 * 60 * 60 =>  'year',

    30 * 24 * 60 * 60       =>  'month',

    24 * 60 * 60            =>  'day',

    60 * 60                 =>  'hour',

    60                      =>  'minute',

    1                       =>  'second'

  );



  foreach( $condition as $secs => $str )

  {

    $d = $time_difference / $secs;

    if( $d >= 1 )

    {

      $t = round( $d );

      return $t . ' ' . $str . ( $t > 1 ? 's' : '' ) . ' ago';

    }

  }

}

///////////// FUNCTIONS FOR BOOKS//////////////////////

/////////////////////////////////////////////////////


 //NORMAL USERS LOGGEN IN ON THE PORTAL//

 function username($mid){
  $get =  QueryDB("SELECT * from users where username ='$mid' ");
  $getter = $get->fetch(PDO::FETCH_ASSOC);
  return $getter['lname'].' '.$getter['fname'].' '.$getter['mname'];
}

 function get_country_code($mid){
  $get =  QueryDB("SELECT sortname from countries where id ='$mid' ");
  $getter = $get->fetch(PDO::FETCH_ASSOC);
  return $getter['sortname'];
}

 function ausername($mid){
  $get =  QueryDB("SELECT * from adminuser where username ='$mid' ");
  $getter = $get->fetch(PDO::FETCH_ASSOC);
  return $getter['lname'].' '.$getter['afname'];
}

function get_user_details($email){
  $get = QueryDB("SELECT * FROM users WHERE username = '$email' ");
  return $get->fetch(PDO::FETCH_ASSOC);
}

function get_all_user(){ $sn =1;
foreach(QueryDB("SELECT * FROM users") as $row){
  
    $username = username($row['username']);
    $email = $row['email'];
    $status = $row['substat'] == 0 ? 'Active' : 'Inactive';
    $passport = !empty($row['passport']) ? $row['passport'] : 'profile.jpg';
    $date = date('M d, Y', strtotime($row['DateCreated']));
    echo "<tr>
     <td>$sn</td>
    <td><img src='../user/assets/img/$passport' class='rounded-circle' width='50' height='50'></td>
           
            <td>$username</td>
            <td>$email</td>
            <td>$status</td>
            <td>$date</td>
          </tr>";
  } $sn++;
}

function get_application( $id){
  $get = QueryDB("SELECT * FROM applications WHERE  house_id='$id' ");
  return $get->fetch(PDO::FETCH_ASSOC);
}

function downlines($id){
  return QueryDB("SELECT COUNT(*) FROM users where sponsor='$id' ")->fetchColumn();
}

function all_users(){
  return QueryDB("SELECT COUNT(*) FROM users ")->fetchColumn();
}
function all_products(){
  return QueryDB("SELECT COUNT(*) FROM products ")->fetchColumn();
}

function all_p_cat(){
  return QueryDB("SELECT COUNT(*) FROM prod_cat ")->fetchColumn();
}

function buildReferralTree($username, $level = 1, $maxDepth = 5, &$cache = [])
{
    if ($level > $maxDepth) return '';

    // Check cache first
    if (isset($cache[$username])) {
        $user = $cache[$username];
    } else {
        // Fetch user and cache it
        $stmt = QueryDB("SELECT * FROM users WHERE username = '$username' LIMIT 1");
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) return ''; // user not found
        $cache[$username] = $user;
    }

    // Display node info
    
    $profileImage = $user['passport']; 
    if( empty( $profileImage)) $profileImage = 'profile.jpg';
    $joinDate = date('M d, Y', strtotime($user['DateCreated']));
    $username = htmlspecialchars($user['username']);

    $html = "<li>
        <div class='user card text-center p-2 shadow-sm'>
            <img src='../user/assets/img/{$profileImage}' class='rounded-circle mb-2' width='60' height='60'>
            <div><strong>{$username}</strong></div>
            <small class='text-muted'>Joined: {$joinDate}</small>
        </div>";

        // Fetch children, grouped by position
          $ref = $user['username'];
    $stmt = QueryDB("SELECT username, position FROM users WHERE sponsor = '$ref' ORDER BY position ASC");
    $children = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Organize children
    $left = null;
    $right = null;

    foreach ($children as $child) {
        if ($child['position'] == 1) $left = $child['username'];
        elseif ($child['position'] == 2) $right = $child['username'];
    }

    if ($left || $right) {
        $html .= "<ul>";

        // Left child
        $html .= "<li>";
        $html .= $left ? buildReferralTree($left, $level + 1, $maxDepth, $cache) : "";
        $html .= "</li>";

        // Right child
        $html .= "<li>";
        $html .= $right ? buildReferralTree($right, $level + 1, $maxDepth, $cache) : "";
        $html .= "</li>";

        $html .= "</ul>";
    }

    $html .= "</li>";
   

    return $html;
}

function get_access_level($mid)
{
  $get = QueryDB("SELECT access FROM adminuser WHERE username = '$mid' ");
 $getter =  $get->fetch(PDO::FETCH_ASSOC);
 return $getter['access'];
}

function get_admin_details($mid)
{
  $get = QueryDB("SELECT * FROM adminuser WHERE username = '$mid' ");
  return $get->fetch(PDO::FETCH_ASSOC);
}


function renderTree($username, $pdo, $level = 1, $maxDepth = 4)
{
    if ($level > $maxDepth) return '';

    // Fetch user info
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch();

    if (!$user) return '';

    $image = !empty($user['passport']) ? $user['passport'] : 'profile.jpg';
    $imgPath = "../user/assets/img/{$image}";
    $name = htmlspecialchars($user['username']);

    $html = "<li>
        <div class='node'>
          <img src='{$imgPath}' alt='{$name}'>
          <div class='name'>{$name}</div>
        </div>";

    // Get children for left and right (position: 1 = left, 2 = right)
    $stmt = $pdo->prepare("SELECT username, position FROM users WHERE sponsor = :ref ORDER BY position ASC");
    $stmt->execute([':ref' => $user['username']]);
    $children = [1 => null, 2 => null];

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $child) {
        $pos = (int)$child['position'];
        if ($pos === 1 || $pos === 2) {
            $children[$pos] = $child['username'];
        }
    }

    if ($children[1] || $children[2]) {
        $html .= "<ul>";

        // LEFT
        $html .= "<li>";
        $html .= $children[1]
            ? renderTree($children[1], $pdo, $level + 1, $maxDepth)
            : "<div class='node no-user'><img src='no-user.png'><div class='name'>No user</div></div>";
        $html .= "</li>";

        // RIGHT
        $html .= "<li>";
        $html .= $children[2]
            ? renderTree($children[2], $pdo, $level + 1, $maxDepth)
            : "<div class='node no-user'><img src='no-user.png'><div class='name'>No user</div></div>";
        $html .= "</li>";

        $html .= "</ul>";
    }

    $html .= "</li>";

    return $html;
}



function lan_house_count($id){
  return QueryDB("SELECT COUNT(*) FROM houses where landlord_id='$id' ")->fetchColumn();
}

function get_username($id){
  $get = QueryDB("SELECT name FROM users WHERE id = '$id' ");
  $getter = $get->fetch(PDO::FETCH_ASSOC);
  return $getter['name'];
}

function total_amount($id){
  $get = QueryDB("SELECT SUM(COALESCE(amount, 0)) AS total_amount FROM applications where tenant_id='$id' ");
  $getter = $get->fetch(PDO::FETCH_ASSOC);
  return $getter['total_amount'] ?? 0;
}

function total_rentamount($id){
  $get = QueryDB("SELECT SUM(COALESCE(amount, 0)) AS total_amount FROM applications where house_id='$id' and status='approved' ");
  $getter = $get->fetch(PDO::FETCH_ASSOC);
  return $getter['total_amount'] ?? 0;
}

function get_house_id($id){
  $get = QueryDB("SELECT id  FROM houses where landlord_id='$id' ");
  $getter = $get->fetch(PDO::FETCH_ASSOC);
  return $getter['id'] ?? 0;
}

function get_payment_details($email){
  $get = QueryDB("SELECT * FROM payment WHERE pay_user = '$email' ");
  return $get->fetch(PDO::FETCH_ASSOC);
  
}


function pay_time($email){
  $get = QueryDB("SELECT confirm_time from payment where pay_user ='$email' ");
  $getter = $get->fetch(PDO::FETCH_ASSOC);
  return $getter['confirm_time'];
}


function get_user_name_from_code($email){
  $get = QueryDB("SELECT fname from f_users where ucode ='$email' ");
  $getter = $get->fetch(PDO::FETCH_ASSOC);
  return $getter['fname'];
}

function get_email_from_code($email)
{
  $get = QueryDB("SELECT email from f_users where track='$email' ");
  $getter = $get->fetch(PDO::FETCH_ASSOC);
  return $getter['email'];
}

function get_user_photo_from_code($email){
  $get = QueryDB("SELECT passport from f_users where ucode ='$email' ");
  $getter = $get->fetch(PDO::FETCH_ASSOC);
  return $getter['passport'];
}

function get_fname($code){
  $get = QueryDB("SELECT fname from f_users where ucode ='$code' ");
  $getter = $get->fetch(PDO::FETCH_ASSOC);
  return $getter['fname'];
}


function get_track_name($code){
  $get = QueryDB("SELECT track_name from tracks where track_id ='$code' ");
  $getter = $get->fetch(PDO::FETCH_ASSOC);
  return $getter['track_name'];
}





////////////////////////////////////////////

/////FEPFL ADMIN ////////

function get_all_stud(){
  return QueryDB("SELECT COUNT(*) from users ")->fetchColumn();
}

function get_all_post(){
  return QueryDB("SELECT COUNT(*) from posts ")->fetchColumn();
}

function get_all_cat(){
  return QueryDB("SELECT COUNT(*) from category ")->fetchColumn();
}

function pay_count(){
  return QueryDB("SELECT COUNT(*) from payment where pay_status ='Approved' ")->fetchColumn();
}


function get_all_students(){
  return QueryDB("SELECT * from details ");
}

function get_f_users(){
  return QueryDB("SELECT * FROM f_users ");
}

function get_all_f_users(){
  return QueryDB("SELECT COUNT(*) FROM f_users ")->fetchColumn();
}
///////////////////////////////////////