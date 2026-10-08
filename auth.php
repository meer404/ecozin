<?php
require_once __DIR__.'/bootstrap.php';
if ($_SERVER['REQUEST_METHOD']!=='POST') { header('Location: index.php'); exit; }
try {
 csrf(); $action=$_POST['action'] ?? 'login';
 if ($action==='logout') { $_SESSION=[]; session_destroy(); header('Location: index.php'); exit; }
 if ($action==='register') {
    $name=trim($_POST['full_name'] ?? ''); $email=trim($_POST['email'] ?? ''); $password=$_POST['password'] ?? ''; $role=$_POST['role'] ?? '';
    if (!$name || strlen($name)>120 || !filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($email)>190 || strlen($password)<10 || !in_array($role,['farmer','business'],true)) throw new RuntimeException('Enter a name, valid email, role, and password of at least 10 characters.');
    $product=$_POST['product'] ?? 'Pomegranate'; if (!in_array($product,['Pomegranate','Walnut','Olive Pomace'],true)) throw new RuntimeException('Invalid material.');
    $profile=json_encode(['industry'=>substr(trim($_POST['industry'] ?? 'Local producer'),0,200),'product'=>$product,'lat'=>35.561,'lng'=>45.435]);
    query('INSERT INTO users(full_name,email,password,role,profile_data) VALUES(?,?,?,?,?)','sssss',[$name,$email,password_hash($password,PASSWORD_DEFAULT),$role,$profile]);
 }
 $email=trim($_POST['email'] ?? ''); $u=query('SELECT * FROM users WHERE email=?','s',[$email])->get_result()->fetch_assoc();
 if (!$u || !password_verify($_POST['password'] ?? '',$u['password'])) throw new RuntimeException('Email or password is incorrect.');
 session_regenerate_id(true); unset($u['password']); $_SESSION['user']=$u; $_SESSION['csrf']=bin2hex(random_bytes(32));
 header('Location: '.($u['role']==='farmer'?'farmer_dashboard.php':'business_dashboard.php')); exit;
} catch(Throwable $e) { $_SESSION['flash']=$e instanceof mysqli_sql_exception?'Could not complete sign-in. Check database setup or use a different email.':$e->getMessage(); header('Location: index.php?view=login'); exit; }
