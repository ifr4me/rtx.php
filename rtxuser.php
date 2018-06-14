<!DOCTYPE html>
<?php
	// src_ip check if accessable?
	$ip = $_SERVER['REMOTE_ADDR'];
	if (substr($ip, 0, 7) != '10.2.26'){
		die();
	}
?>
<html>
        <head>
                <title>RTXUSER</title>
                <meta charset='utf8'>
        </head>
        <body>
        <center>
                <form action='' method="POST">
                        <br><br/>
                        <input type="text" value="" name="searchkey" style="width:400px;height:24px;"/>
                        <select name='searchfield' style="height:30px">
                                <option value="user_id">EmployeeID</option>
                                <option value="email">Email</option>
                                <option value="account">Account</option>
                                <option value="ip" selected>IP</option>
                                <option value="username_cn">姓名</option>
                                <option value="mobile">mobile</option>
                                <option value="phone">phone</option>
                                <option value="location">location</option>
                        </select>
                        <select name='searchway' style="height:30px">
                                <option value="1">完全匹配</option>
                                <option value="2">右侧匹配</option>
                                <option value="3">左侧匹配</option>
                                <option value="4" selected>左右匹配</option>
                        </select>
                        <input type='submit' name='searchbtn' value='search' style="height:30px"/><br /><br />
                </form>
        </center>
<?php
        header("Content-type: text/html; charset=utf8");
	#error_reporting(0);

        $basename="test";//填写社工数据库名字
        @$searchkey= str_replace('%', '', addslashes($_POST['searchkey']) );
	if (strlen($searchkey)<4){
		echo '<center>关键词太短！<center>';
		die();
	}
        $conn = mysql_connect("127.0.0.1","test","xxxooo");//连接mysql数据库
	mysql_query("set names utf8");
        if(isset($_POST['searchbtn'])&& strlen(trim($searchkey))>0){
                $searchfield = str_replace('%', '', addslashes($_POST['searchfield']) );
		#echo 'field='.$searchkey.'<br>';
                $searchway = addslashes($_POST['searchway']);
                $all_colums=0;//定义社工库总行数初始值为0
                $current_colums=0;//定义完成检索的总行数数初始值为0
                $current_colums_intable=0;//定义表内的当前检索行数初始值为0
                mysql_select_db("information_schema",$conn); //选择基础数据库用于读取社工库的表清单
                $sql_get_fields="select `table_rows` from `TABLES` where `table_schema`='".$basename."'";//获取表名用于逐表检索、表行数用于计算分总进度和字符集设置每个表的输出字符格式
                $query_tables = mysql_query($sql_get_fields);
                //以下是计算时间的函数
                function get_microtime_array()
                        {
                            return explode(' ', microtime());
                        }
                while($get_table_info = mysql_fetch_assoc($query_tables)){
                        $all_colums=$all_colums + $get_table_info['table_rows'];//累加得到社工库总行数
                }
                $sql_get_match_tables="select `table_name` from COLUMNS where `table_schema`='".$basename."' and `COLUMN_NAME`='".$searchfield."'";//根据传递过来的检索字段确定社工库中存在该字段的分别
                $query_match_tables = mysql_query($sql_get_match_tables);                
                //以下将进入社工库检索
                while($get_match_tables = mysql_fetch_assoc($query_match_tables)){
                        $start_time_array = get_microtime_array();//获取每次查询开始的时间
                        mysql_select_db($basename,$conn); //选择社工库
                        //以下为判断检索方式的switch四个分支
                        switch ($searchway){
                                case 1://完全匹配方式
                                        $sql_get_rows = "select * from ".$get_match_tables['table_name']." where $searchfield = '".$searchkey."'";
                                          break;
                                case 2://右侧匹配方式
                                        $sql_get_rows = "select * from ".$get_match_tables['table_name']." where $searchfield like '".$searchkey."%'";
                                         break;
                                case 3://左侧匹配方式
                                        $sql_get_rows = "select * from ".$get_match_tables['table_name']." where $searchfield like '%".$searchkey."'";
                                         break;
                                case 4://模糊匹配方式
                                        $sql_get_rows = "select * from ".$get_match_tables['table_name']." where $searchfield like '%".$searchkey."%'";
                                         break;
                                default://默认则为完全匹配方式
                                        $sql_get_rows = "select * from ".$get_match_tables['table_name']." where $searchfield = '".$searchkey."'";
                        }
                        $query_rows = mysql_query($sql_get_rows);
                        $result_rows=mysql_num_rows($query_rows);
                        if($result_rows){
                                $cols=mysql_num_fields($query_rows);//返回表的字段数。用于下面标头跨列数
                                echo "<pre>";
                                echo "<center><table border='1' style='width:800px;'><tr><th colspan='".$cols."'>在".$get_match_tables['table_name']."中检索出<font color=red>".$result_rows."</font>条相关信息</th></tr><tr>";
                            ob_flush();
                                  flush();
                                $table_keys="";
                                $table_values="";
                                foreach(mysql_fetch_assoc($query_rows) as $k=>$v){
                                        $table_keys .= "<th>$k</th>";
                                        $table_values .= "<td>$v</td>";
                                }
                                echo $table_keys_inline = "<tr>$table_keys</tr>";
                                echo $table_values_inline = "<tr>$table_values</tr>";
                            ob_flush();
                                  flush();
                                while($get_values=mysql_fetch_assoc($query_rows)){
                                        $current_colums_intable=$current_colums_intable+1;//记录表内检索行数，用它除以表的行数（也就是$get_table_info['table_rows']）就可以显示表内查询进度条--留给大牛们用ajax实现
                                        $current_colums=$current_colums+$current_colums_intable;//记录所有表完成检索的行数，用它除以社工库总行数（也就是$all_colums）就可以于显示全局查询进度条--留给大牛们用ajax实现
                                        echo "<tr>";
                                        foreach($get_values as $v){
                                                echo "<td>".$v."</td>";
                                            ob_flush();
                                                  flush();
                                        }
                                echo "</tr>";
                                }
                                $current_colums_intable=0;//单表查询结束后清零
                                $end_time_array = get_microtime_array();//获取php结束时间
                                $time=$end_time_array[0] + $end_time_array[1] - $start_time_array[0] - $start_time_array[1];//计算运行时间，单位秒
                                $table_query_time= "该表查询耗时：".round($time*1000)."毫秒。";//换算成毫秒取整输出                        
                        echo "<tr><th colspan='".$cols."'>".$table_query_time."</th></tr></table><br /></center>";
                        echo "</pre>";
			}else{
	                        echo '<center><font color="red">Not Found!</font></center>';
			}
                }
        }
        mysql_close($conn);

?>
        </body>
</html>
