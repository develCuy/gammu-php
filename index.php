<?php /*
   * Gamu-PHP v0.0.6
   * http://develcuy.elblog.de/gammu-php
   */?>
<?php
define('DEBUG',1);
?>
<html>
<head>
</head>
<body>
<?php // Get path
$path = @$_GET['p'] == '/' ? '' : stripslashes(@$_GET['p']);
$file = @$_GET['f'];
$params = "&f=$file";
$action = @$_GET['a'] == '' ? 'getfolderlisting' : @$_GET['a'];

// Parse Path
$_separator = ' / ';
$_path_prefix = '<a href="?a=getfolderlisting&p=';
$_path = $_path_prefix.'/'.$params.'">root</a> ';
$__path = explode('/',$path);
if(count($__path) > 1){
  $_path = $_path.$_separator;
  for($_i = 0; $_i < count($__path); $_i++){
    // Param separator
    if($_i > 0)
      @$___path.= '/'.$__path[$_i];
    else
      @$___path.= $__path[$_i];
    
    // Output
    @$_path.= $_path_prefix.$___path.$params.'">'.$__path[$_i].'</a>'.$_separator;
  }
}
$params = "&p=$path".$params;

?>
<?php // Menu?>
<div class="menu">
<a href="?a=help">Help</a>
<a href="?a=monitor">Monitor</a>
<a href="?a=identify">Identify</a>
</div><br />

<div class="path">
Path: <?php print $_path;?>
</div><br />
<?php

// Root folders
if($action == 'getfolderlisting' and $path == '')
  $action = 'getrootfolders';

// Multiple downloads
if(count($_POST) && $action == 'getfiles' && isset($_POST['filename'])){
  $path = array();
  $chunk_size = 30; // number of files to download per try
  $download = array();
  $_j = 0;
  $_chunk_buffer = array();
  $_chunk_glue = '" "';
  for($_i = 0; $_i < count($_POST['filename']); $_i++){ $_j++;
    $_name = explode('|',$_POST['filename'][$_i]);
    $path[] = $_name[0];
    $_chunk_buffer[] = $_name[0];
    if($chunk_size == $_j || $_i+1 == count($_POST['filename'])){
      $download[] = implode($_chunk_glue, $_chunk_buffer);
      $_chunk_buffer = array();
      $_j = 0;
    }
  }
  $path = implode($_chunk_glue, $path);
}

// Params
$params = array(
 'help' => $path,
 'monitor' => '',
 'identify' => '',
 'getfolderlisting' => '"'.$path.'"',
 'getrootfolders' => '',
 'getfiles' => '"'.$path.'"',
 'deletefiles' => '"'.$path.'"'
);
$confpath = 'HOME='.dirname(__FILE__);
define('ACTION',$action);

// Parser
function parseout($line, $status){
  /* Posible status values :
  PHP_OUTPUT_HANDLER_START
  PHP_OUTPUT_HANDLER_CONT
  PHP_OUTPUT_HANDLER_END
  */
  if($status != PHP_OUTPUT_HANDLER_END){
    switch(ACTION){
      case 'getfiles':
        $line = implode('%<br />',explode('percent',$line));
        break;
      case 'getrootfolders':
        $_command = getfolderlisting;
      case 'help':
        if(ACTION == 'help')$_command = ACTION;
        if(!(strpos($line, ' - ') === false)){
          $_line = explode(' - ',$line);
          $_line[0] = trim($_line[0]);
          $line = '<tr>'.
            '<td><a href="?a='.$_command.'&p='.$_line[0].'">'.$_line[0]."</a></td>".
            '<td>'.$_line[1].'</td>'.
          '</tr>';
        }else
          $line = '<pre>'.$line.'</pre>';
        break;
      case 'getfolderlisting':
        $_line = explode(';',$line);
        
        // Command
        if($_line[1] == 'File'){
          $_command = 'getfiles';
          $_file = '&f='.str_replace('"','',$_line[2]);
          $_delete = '<a href="/?a=deletefiles&p='.$_line[0].'">X</a>';
          $_choose = '<input type="checkbox" name="filename[]" value="'.$_line[0].'|'.$_line[2].'">';
        }else
          'getfolderlisting';
        
        // Icon
        if($_line[1] == 'Folder') $_icon = 'dir';
        else $_icon = 'unknown';
        $_icon = '<img src="/icons/'.$_icon.'.gif"> ';
        
        // Item type
        $_type = $_line[1];
        
        // Link
        $_uri = '?a='.$_command.'&p='.$_line[0].$_file;
        $_link = '<a alt="'.$_uri.'" title="'.$_uri.'" href="'.$_uri.'">'.$_line[2].'</a> ';
        
        // Properties
        $_props = $_line[3];
        
        // Output
        $line = '<tr>'.'<td>'.implode('</td><td>', array(
          $_icon,
          //$_type,
          $_link,
          $_props,
          $_delete,
          $_choose,
        )).'</td>'.'</tr>';
        break;
    }
  }
  return $line;
}

// Execute
if($action!=''){
  
  // Clear another gammu instances
  system('killall gammu');
  
  // Ensure to delete downloaded file
  if($action == 'getfiles' && !count($download)){
    $_file[0] = 'rm -rf "'.$file.'" &&';
    $_file[1] = '&& chmod a+w "'.$file.'"';
    //echo $_file[1];die();
  }
  
  // Construct command according to param
  $command = 'gammu --'.$action.' '.$params[$action];
  
  function get_system_command($command, $file, $confpath){
    $command = 
      'cd /tmp && '. //Path to download files
      @$file[0].' '.
        $confpath.' '. //Path to found gammu config
        $command.' 2>&1'.' '.
      @$file[1]
    ;
    return $command;
  }
  
  ?><div class="command">command: <?php print $command;?></div><br /><?php

  // Execution
  if(!count($download)){
    ?><table><form action="?a=getfiles&p=<?php print $action;?>" method="POST"><?php
  }
    ob_start('parseout',2);
    if(count($download)){
      for($_i = 0; $_i < count($download); $_i++){
        set_time_limit(360);
        $command = 'gammu --'.$action.' "'.$download[$_i].'"';
        $command = get_system_command($command, $file, $confpath);
        system($command, $status);
      }
    }else{
      $command = get_system_command($command, $file, $confpath);
      system($command, $status);
    }
    ob_end_flush();
  if(!count($download)){
    ?></table><?php
    if(ACTION == 'getfolderlisting'){?>
      <input type="submit" value="download" /><?php
    }
    ?></form><?php
  }
  
  // Debug
  if(DEBUG) {
    ?><div class="command">debug: <?php print $command;?></div><br /><?php
    ?>exit: <?php print $status;?><?php
  }
}
?>
</body>
</html>
