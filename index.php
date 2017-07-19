<?php
define('MYSQL_HOST', 'localhost');
define('MYSQL_USER', 'root');
define('MYSQL_PASS', '');
define('MYSQL_DB', 'itrust');
$statuses = array(1=>"Raw Draft", 2=>"Reviewed Draft, open RNs", 3=>"Reviewed Draft, closed RNs", 4=>"Final paper");

$cid = mysql_connect (MYSQL_HOST, MYSQL_USER, MYSQL_PASS);
if (!mysql_select_db (MYSQL_DB, $cid)) die("couldn't open the database");

session_start();

$url = $_SERVER['REQUEST_URI'];
list($url) = explode('?', $url);
$url = explode('/', $url); 
array_pop($url);

function getUsers() {
	global $users;
	if (empty($users)) {
		$qid = mysql_query("SELECT * FROM users ORDER BY name");
		while ($row = mysql_fetch_assoc($qid)) {
			$users[$row['id']] = $row;
		}
	}
	return $users;
}

function getEngagementSections($eng_id, $pid=0, $level=1) {
	global $tree;
	$result = '';
	
	foreach($tree[$pid] as $row) {
		$result .= "<option value='{$row['id']}'>".str_repeat("&nbsp;", $level*5)."{$row['title']}</option>";
		if (isset($tree[$row['id']])) 
			$result .= getEngagementSections($eng_id, $row['id'], $level+1);
	}
	return $result;
}

function PrintWorkpapersTree($pid, $level=1) {
	global $tree, $users, $files, $statuses;

// preparers = array( userid=>date signed, userid=>date signed)
	if (is_array($tree[$pid]))
	foreach ($tree[$pid] as $id=>$row) {
		$padding = $level*20;

		if (isset($files[$row['file_id']])) {
			$row['preparers'] = empty($files[$row['file_id']]['preparers']) ? array() : unserialize($files[$row['file_id']]['preparers']);
			$row['reviewers'] = empty($files[$row['file_id']]['reviewers']) ? array() : unserialize($files[$row['file_id']]['reviewers']);

			$p = array();
			foreach ($row['preparers'] as $id=>$dateline) 
				$p[] = "<acronym title='{$users[$id]['name']} signed at ".date("d/m/Y [h:i]", $dateline)."'>{$users[$id]['signoff']}</acronym>";  
			$preparers = implode(', ', $p); 
			
			$p = array();
			foreach ($row['reviewers'] as $id=>$dateline)
				$p[] = "<acronym title='{$users[$id]['name']} signed at ".date("d/m/Y [h:i]", $dateline)."'>{$users[$id]['signoff']}</acronym>";
			$reviewers = implode(', ', $p);
			
			$status = $statuses[$row['status']];
			$date = date("d/m/Y [h:i]", $files[$row['file_id']]['dateline']);
			$buttons = "[<a title='{$files[$row['file_id']]['name']}' href='/files/{$row['eng_id']}/{$row['file_id']}.{$files[$row['file_id']]['fileext']}'>DOW</a>] &nbsp; 
						[<a href='/engagement/{$row['eng_id']}/{$row['id']}/'>EDT</a>] &nbsp; 
						[<a href='/engagement/{$row['eng_id']}/{$row['id']}/'>DEL</a>]
						";
		} else {
			$preparers = $reviewers = $status = $date = '';
			$buttons = "[<a href='/engagement/{$row['eng_id']}/{$row['id']}/'>Edit</a>]";
		}

		echo "<tr>
			<td style='padding-left:{$padding}'>".htmlspecialchars($row['title'])."</td>";
		echo "
			<td align='center'>{$preparers}</td>
			<td align='center'>{$reviewers}</td>
			<td align='center'>{$date}</td>
			<td align='center'>{$status}</td>
			<td width='200' align='center'>{$buttons}</td>
		</tr>";
		if (isset($tree[$row['id']]))
			PrintWorkpapersTree($row['id'], $level+1);
	}
}


if (!isset($_SESSION['user']))  {
	
	if ($_SERVER['REQUEST_METHOD']=='POST') {
		$user = $_POST['login'];
		$pass = $_POST['pass'];

		$qid = mysql_query($sql="SELECT * FROM users WHERE (name='".addslashes($user)."') AND pass='".md5($pass)."'");
		if ($qid && mysql_num_rows($qid)>0) {
			// valid login
			$_SESSION['user'] = mysql_fetch_assoc($qid);
			$error = "successful_login";
		} else {
			// wrong pass
			$error = "wrong_password";
		}
	} else {
		$error = "need_login";	
	}
} else {
	$error = "successful_login";
}
?>


<html>
<head>
	<meta http-equiv="content-type" content="text/html; charset=windows-1251" />
	<title>iTrust Audit System</title>
	<link type="text/css" href="/images/style.css" rel="stylesheet" />
</head>
<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0'>

<div id="head">
	<div class='l'>
		<a href='/'><img src='/images/logo.png'></a>
	</div>
	<div class='r'>
		<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://pdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,0,0" id="lecteur" width="735" height="179">
			<param name="wmode" value="transparent">
			<param name="movie" value="/images/itrust_flash.swf">
			<param name="allowScriptAccess" value="never">
			<embed allowscriptaccess="never" type="application/x-shockwave-flash" src="/images/itrust_flash.swf" wmode="transparent" width="735" height="179">
		</object>
	</div>
</div>
<?php

	if ($error == "successful_login") {
		echo "<div id='menu'><table width='30%' cellpadding='0' cellspacing='0' border='0'><tr>";

		$menu = array("."=>"Home page", "account"=>"Edit account", "logout"=>"Logout");		
		foreach($menu as $k=>$v) {
			echo "<td".(1==1?" class='active'":"")."><a href='/$k/'>$v</a></td>";
		}

		echo "	</tr></table></div>";
	}
?>
<script>
	function createnewengagement() {
		location.href = "/newengagement/";
	}
	function editengagement(id) {
		location.href = "/editengagement/"+id+"/";
	}
	function delengagement(id) {
		location.href = "/delengagement/"+id+"/";
	}
</script>
<div id="main">
<?php

	if ($error == "need_login" || $error == "wrong_password") {
		echo "<div class='loginform'><h1>";
		echo  ($error=="need_login" ? " You need to log in: " : "Wrong username or password! Please try again") ;
		echo "</h1><form method=post>
			Login:  <input type=text name=login class='login'> <br>
			Password:  <input type=password name=pass class='pass'> <br>
			<input type=submit value=Login class='submit'>
		</form>
		</div>";
	}
	else
	if ($url[1]=="logout") { // lof out
		unset($_SESSION['user']);
		echo "<h1>You have successfully logged out</h1> <script>setTimeout(\"location.href='/'\", 2000)</script>";		
	} else
	if ($url[1]=="account") {  // edit account settings
		echo "account settings edit";
	}
	elseif ($url[1]=="newengagement") {
		echo "<h3>Adding new engagement </h3>
			<form method='post'>
			Engagement title: <input type='text' name='title' value=''> <br> 
			<input type='submit' value='Add new'> 
			</form>";
		
	}
	elseif ($url[1]=="editengagement") {
		echo "Editing the engagement" ;
		$qid = mysql_query("SELECT * FROM engagements WHERE id={$url[2]}");
		if (!$qid) {
			echo "There is no such engagement";
		} else {
			$row = mysql_fetch_assoc($qid);
			$dateline = date('d/m/Y',$row['dateline']);

			if ($_SERVER['REQUEST_METHOD']=='POST') {
				list($title, $dateline) = array($_POST['title'], $_POST['dateline']);
				list($d,$m,$y) = explode('/', $dateline);
				$dateline = mktime(0,0,0,$m,$d,$y);
				$title = addslashes($title);
				$qid = mysql_query("UPDATE engagements SET title='{$title}', dateline={$dateline} WHERE id='{$row['id']}'");
				if ($qid) {
					echo "<h3>The engagement has been successfully updated. <br><br>Please wait to be redirected to the main page </h3><script>setTimeout(\"location.href='/'\", 2000)</script>";
				} else {
					echo "An error has occured while updating the engagement, please report to the administration <br>";
				}
			} else {
				echo "<br> &nbsp; <form method='post'>
					Engagement title: <input type='text' class='eng_title' name='title' value='{$row['title']}'> <br>
					Creation date: <input type='text' class='eng_date' name='dateline' value='{$dateline}'> <br> <br> 
					<input type='submit' value='Change'> 
				</form>";
			}
		}
	}
	elseif ($url[1]=="delengagement") {
		$url[2] = intval($url[2]);
		$qid = mysql_query("DELETE FROM engagements WHERE id='{$url[2]}'");
		if ($qid) {
			echo "<h3>The engagement has been successfully deleted. <br><br>Please wait to be redirected to the main page </h3><script>setTimeout(\"location.href='/'\", 2000)</script>";
		} else {
			echo "An error has occured while updating the engagement, please report to the administration <br>";
		}
	}

	elseif ($url[1]=="engagement" && is_numeric($url[3])) { // edit WP
		$eng_id = intval($url[2]);
		$wp_id = intval($url[3]);

		$qid3 = mysql_query("SELECT * FROM workpapers WHERE eng_id={$eng_id} ORDER BY pid,sequence");
		$tree = array();
		while ($row = mysql_fetch_assoc($qid3)) {
			if ($row['file_id']==0)
				$tree[$row['pid']][$row['id']] = $row;
			if ($row['id']==$wp_id) $workpaper = $row;
		}

		$qid = mysql_query("SELECT * FROM files WHERE (wp_id={$wp_id}) AND (eng_id={$eng_id})");
		if ($qid && mysql_num_rows($qid)) {
			$file = mysql_fetch_assoc($qid);

			$pids = "<option value='0'>Main section</option>";
			$pids .= getEngagementSections($eng_id);
			$pids = str_replace("<option value='{$workpaper['pid']}'>", "<option selected value='{$workpaper['pid']}'>", $pids);

			$file['preparers'] = unserialize($file['preparers']);
			$file['reviewers'] = unserialize($file['reviewers']);
			$prep_checked 	= isset($file['preparers'][$_SESSION['user']['id']]) ? "checked" : "";
			$review_checked = isset($file['reviewers'][$_SESSION['user']['id']]) ? "checked" : "";

			if ($_SERVER['REQUEST_METHOD']=='POST') {
				if (!is_dir("files/{$eng_id}")) {
					mkdir("files/{$eng_id}");
					chmod("files/{$eng_id}", 0777);
				}
				
				$pid = intval($_POST['pid']);
				$title = $_POST['workpapers_title'];
				
				if ($_POST['preparer']==1) { $file['preparers'][$_SESSION['user']['id']] = time(); } 
				else { unset($file['preparers'][$_SESSION['user']['id']]); }
				
				if ($_POST['reviewer']==1) { $file['reviewers'][$_SESSION['user']['id']] = time(); } 
				else { unset($file['reviewers'][$_SESSION['user']['id']]); }
				
				$status = intval($_POST['workpapers_status']);

				if (is_file($_FILES['files_name']['tmp_name'])) {
				
					$file_ext = strtolower(end(explode('.', $_FILES['files_name']['name'])));
					$file_name = $_FILES['files_name']['name'];

					if ($workpaper['file_id']!=0) {  // delete old file, upload new file and UPDATE sql query
						$old_fname = "files/{$eng_id}/{$wp_id}.{$file['fileext']}";
						if (is_file($old_fname)) unlink($old_fname);
						$file_id = $workpaper['file_id'];
						
						$fname = "files/{$eng_id}/{$wp_id}.{$file_ext}"; 
						if (is_file($fname)) unlink($fname);
						
						if (is_file($_FILES['files_name']['tmp_name'])) {
							move_uploaded_file($_FILES['files_name']['tmp_name'], $fname);
							chmod($fname, 0777);
						}
						mysql_query("UPDATE files SET name='".addslashes($file_name)."', dateline=UNIX_TIMESTAMP(), preparers='".addslashes(serialize($file['preparers']))."', reviewers='".addslashes(serialize($file['reviewers']))."', fileext='".addslashes($file_ext)."' WHERE id={$file['id']}");
						
					} else { // upload new file, INSERT sql query

						$fname = "files/{$eng_id}/{$wp_id}.{$file_ext}";
						if (is_file($fname)) unlink($fname);
						move_uploaded_file($_FILES['files_name']['tmp_name'], $fname);
						chmod($fname, 0777);
						
						$qid = mysql_query("INSERT INTO files(eng_id, wp_id, name, dateline, preparers, reviwers, fileext) VALUES ({$eng_id}, {$wp_id}, '".addslashes($file_name)."', UNIX_TIMESTAMP(), {$preparer}, {$reviewer}, '".addslashes($file_ext)."')");
						$file_id = mysql_insert_id();
					} 
				} else {
					$file_id = $workpaper['file_id'];
					mysql_query("UPDATE files SET dateline=UNIX_TIMESTAMP(), preparers='".addslashes(serialize($file['preparers']))."', reviewers='".addslashes(serialize($file['reviewers']))."' WHERE id={$file['id']}");
				}
				
				$qid = mysql_query("UPDATE workpapers SET pid={$pid}, title='".addslashes($title)."', status={$status}, file_id={$file_id} WHERE id={$workpaper['id']}");
				if ($qid) {
					echo "<h3>The workpaper has been successfully changed. <br><br>Please wait to be redirected to the main page </h3><script>setTimeout(\"location.href='/engagement/{$eng_id}/'\", 2000)</script>"; $redir = true;
				} else {
					echo "An error has occured while updating the engagement, please report to the administration <br>";
				}
				
			}
			
			if (!isset($redir)) {
				echo "<h3>Editing the workpaper</h3>
				<form method='post' enctype='multipart/form-data'>
				Engagement Section: <select name='pid' style='width:350px'>{$pids}</select> <br>
				Workpaper title: <input style='width:350px;' type='text' name='workpapers_title' value='{$workpaper['title']}'> <br>
				File name: <input style='width:350px;' type='file' name='files_name' value='{$file['name']}'> <br>
				<label for='prep'>Sign as <i>Preparer</i>: </label>      <input type='hidden' name='preparer' value='0'> <input type='checkbox' name='preparer' value='1' $prep_checked><br>
				<label for='review'>Sign as <i>Reviewer</i>: </label> <input type='hidden' name='reviewer' value='0'>  <input type='checkbox' name='reviewer' value='1' $review_checked><br>
				Status of the workpaper: <select name='workpapers_status'>";
				foreach ($statuses as $k=>$v)
					echo "<option value='{$k}'".($k==$workpaper['status'] ? "selected" : "").">{$v}</option>";
				echo "</select> <br>
				<input type='submit' value='Change'>
				</form>
				
				Go back to <a href='/engagement/{$eng_id}/'>Engagement workpapers list</a>.";
			}
			
		} else { // editing section instead of WP
			$eng_id = intval($url[2]);
			$wp_id = intval($url[3]);

			$pids = "<option value='0'>Main section</option>";
			
			$pids .= getEngagementSections($eng_id);
			$pids = str_replace("<option value='{$workpaper['pid']}'>", "<option selected value='{$workpaper['pid']}'>", $pids);

			if ($_SERVER['REQUEST_METHOD']=='POST') {
				$pid = intval($_POST['pid']);
				$title = addslashes($_POST['workpapers_title']);
				$qid = mysql_query("UPDATE workpapers SET pid={$pid}, title='{$title}' WHERE id={$wp_id}");
				if ($qid) echo "<h3>The workpaper has been successfully changed. <br><br>Please wait to be redirected to the main page </h3><script>setTimeout(\"location.href='/engagement/{$eng_id}/'\", 2000)</script>";
			} else
			
			echo "<h3>Editing the section</h3> <form method='post'>
			Parent section: <select name='pid' style='width:350px'>{$pids}</select> <br>
			Section  title: <input style='width:350px;' type='text' name='workpapers_title' value=\"{$workpaper['title']}\"> <br> 
			<input type='submit' value='Change'></form>
			";
			
			//echo "There is no such workpaper in this engagement file";
		}
	}
	
	elseif ($url[1]=="engagement" && is_numeric($url[2]) && $url[3]=="addworkpaper") { // add new WP
		$eng_id = intval($url[2]);

		if ($_SERVER['REQUEST_METHOD']=='POST') {
			$pid = intval($_POST['pid']);
			if (ini_get('magic_quotes_gpc')!='On')
				$title = addslashes($_POST['workpapers_title']);
			$sequence = (is_resource($qid=mysql_query("SELECT MAX(sequence) FROM workpapers WHERE pid='{$pid}'")) ? intval(pos(mysql_fetch_row($qid))) : 0);
			$status = intval($_POST['workpapers_status']);
			
			if (isset($_FILES['files_name']) && is_file($_FILES['files_name']['tmp_name'])) {
				$file_ext = end(explode('.', $_FILES['files_name']['name']));

				$qid = mysql_query("SHOW TABLE STATUS LIKE 'workpapers'");
				$row = mysql_fetch_assoc($qid);
				$wp_id = $row['Auto_increment'];
				
				$fname = "files/{$eng_id}/{$wp_id}.{$file_ext}";
				move_uploaded_file($_FILES['files_name']['tmp_name'], $fname);
				chmod($fname, 0777);
				
				$preparers = addslashes(serialize($_POST['preparer']==0 ? array() : array($_SESSION['user']['id']=>time())));
				$reviewers = addslashes(serialize($_POST['reviewer']==0 ? array() : array($_SESSION['user']['id']=>time())));
				
				mysql_query("INSERT INTO files (eng_id, wp_id, name, dateline, preparers, reviewers, fileext) VALUES ({$eng_id}, {$wp_id}, '".addslashes($_FILES['files_name']['name'])."', UNIX_TIMESTAMP(), '{$preparers}', '{$reviewers}', '".addslashes($file_ext)."')");
				$file_id = mysql_insert_id();
			} else {
				$file_id = 0;
			}
			
			mysql_query($sql="INSERT INTO workpapers (eng_id, pid, title, sequence, file_id, status) VALUES ({$eng_id}, {$pid}, '{$title}', '{$sequence}', {$file_id}, {$status})");
			echo "<h3>The workpaper has been successfully added. <br><br>Please wait to be redirected to the main page </h3><script>setTimeout(\"location.href='/engagement/{$eng_id}/'\", 2000)</script>"; $posted = true;
		} else {
			$_POST['preparer'] = $_POST['reviewer'] = $_POST['workpapers_title'] = $_POST['workpapers_status'] = '';
		}
		


		$qid3 = mysql_query("SELECT * FROM workpapers WHERE eng_id={$eng_id} ORDER BY pid,sequence");
		$tree = array();
		while ($row = mysql_fetch_assoc($qid3)) {
			if ($row['file_id']==0)
				$tree[$row['pid']][$row['id']] = $row;
		}
	
	
		$pids = "<option value='0'>Main section</option>";
		$pids .= getEngagementSections($eng_id);
		$prep_checked = $_POST['preparer']==1 ? "checked" : "";
		$review_checked = $_POST['reviewer']==1 ? "checked" : "";
		
		if (!isset($posted)) {
			echo "<h3>Add new workpaper</h3>
			<form method='post' enctype='multipart/form-data'>
			Engagement Section: <select name='pid' style='width:350px'>{$pids}</select> <br>
			Workpaper title: <input style='width:350px;' type='text' name='workpapers_title' value='{$_POST['workpapers_title']}'> <br>
			File name: <input style='width:350px;' type='file' name='files_name' value=''> <br>
			<label for='prep'>Sign as <i>Preparer</i>: </label>			<input type='hidden' name='preparer' value='0'> <input type='checkbox' name='preparer' value='1' $prep_checked><br>
			<label for='review'>Sign as <i>Reviewer</i>: </label>		<input type='hidden' name='reviewer' value='0'>  <input type='checkbox' name='reviewer' value='1' $review_checked><br>
			Status of the workpaper: <select name='workpapers_status'>";

			foreach ($statuses as $k=>$v)
				echo "<option value='{$k}'".($k==$_POST['workpapers_status'] ? "selected" : "").">{$v}</option>";
				
			echo "</select> <br>
			<input type='submit' value='Change'>
			</form>
			
			Go back to <a href='/engagement/{$eng_id}/'>Engagement workpapers list</a>.";
		}
	}

	elseif ($url[1]=="engagement" && isset($url[2])) { // view engagement WP tree
		$eng_id = intval($url[2]);
		$qid = mysql_query("SELECT * FROM workpapers WHERE eng_id={$eng_id} ORDER BY pid,sequence");
		if (!$qid) {
			echo "<p>There is no any workpapers in this engagement </p>";
		} else {
			$tree = array();
			while ($row = mysql_fetch_assoc($qid)) {
				$tree[$row['pid']][$row['id']] = $row;
			}

			if ($qid = mysql_query("SELECT * FROM files WHERE eng_id={$eng_id}")) {
				while ($row = mysql_fetch_assoc($qid)) {
					$row['fileurl'] = "files/{$eng_id}/{$row['id']}.{$row['fileext']}"; 
					$files[$row['id']] = $row;
				}
			}
 
			if ($qid = mysql_query("SELECT * FROM users")) {
				while ($row = mysql_fetch_assoc($qid)) {
					$users[$row['id']] = $row;
				}
			}
 
			if ($qid = mysql_query("SELECT * FROM engagements WHERE id={$eng_id}"))
				$row = mysql_fetch_assoc($qid);
			echo "<h3><span style='font-weight:normal;color:black'>Engagement: </span>{$row['title']}</h3>
			
			<a href='/engagement/{$eng_id}/addworkpaper/'>Add new workpaper</a>
			
			<table width='100%' class='workpapers' cellpadding='0' cellspacing='0' align='center'>
			<tr style='background:silver'>
				<th>Workpaper title</th>
				<th>Preparers</th>
				<th>Reviewers</th>
				<th>Last modified at</th>
				<th>Status</th>
				<th>Action</th>
			</tr>";
			PrintWorkpapersTree(0);
			echo "</table>";
		}
	}

	elseif ($url[1]=="") {
		echo "<h3>Welcome {$_SESSION['user']['name']}</h3>";
		echo "Please choose an engagement to work with: <br>";
		// show engagements list
		$qid = mysql_query("SELECT * FROM engagements ORDER BY dateline DESC, id ASC");
		if ($qid) {
			echo "<table class='engagements' align='center' cellpadding='0' cellspacing='0'>
			<tr style='background:silver'>
				<th>Engagement name</th>
				<th width='100'>Creation date</th>
				<th width='200'>Action</th>
			</tr>";
			while ($row = mysql_fetch_assoc($qid)) {
				$date = date('M d, Y', $row['dateline']);
				echo "<tr>
					<td><a href='/engagement/{$row['id']}/'>{$row['title']}</a></td>
					<td>{$date}</td>
					<td><input type='button' value='Edit' onclick='editengagement({$row['id']})'>  <input onclick=\"if(confirm('Do you really want to delete engagement `{$row['title']}`?'))delengagement({$row['id']});\" type='button' value='Delete'></td>
				</tr>";
			}		
			echo "</table>";
		}
		echo "You can also <input type='button' value='Create new' onClick='createnewengagement()'> engagement";
	}
?>
</div>

<div id="bottom">
	<div class="l">
		Powered by iTrust Audit System verion 1.0<br>
		All rights are reserved
	</div>
</div>
<div class="cl">&nbsp;</div>
</body>
</html>
