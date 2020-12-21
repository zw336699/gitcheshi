<?php
 /**================================
  *后台权限管理相关（grantAction）
  * @author Kevin
  * @email 254056198@qq.com
  * @version 1.0 data
  * @package 游戏公会联盟后台管理系统
 ==================================*/
ACCESS_GRANT != true && exit("forbiden!");
class grantAction extends Action{
    //用户组管理
    public function group(){
        global $channelGroup;
        $postData = getRequest();
        if($postData['api']=="switch"){  //状态切换

            if(intval($postData['id']) <=1)
                ajaxReturn(C('Error:AccessError'), 300);  
            $this ->model ->setTable(ADMINGROUP);
            $this ->model ->setKey('gId');
            $arr=array(
                'updUser' =>$this ->adminfo['uid'],
                'updTime' =>time(),
                'gId' =>intval($postData['id']),
                'gState' =>0
            );
            intval($postData['state']) ==0 && $arr['gState']=1;
            $this ->model ->upRecordByKey($arr);
            $r= intval($postData['state']) ==0 ? '可用':'不可用';
            parent::SystemDoLog('修改权限组ID('.$arr['gId'].')状态为'.$r.' ！');
            ajaxReturn(C('Ok:Operate'));
        }

        //print_r($this ->adminfo);
        //处理api参数增删改操作
        if($postData['api']=='add'){
            if($postData['id'] > 0){
                $this ->model ->setTable(ADMINGROUP);
                $this ->model ->setKey('gId');
                $gInfo = $this ->model ->getOneRecordByKey(intval($postData['id'])); 
                if($gInfo['dGroup'] || $gInfo['dGroup'] !='all'){
                    $gInfo['dataGroup'] = explode(',',$gInfo['dGroup']); 
                }
                $gInfo['gamePermissionsList'] = $gInfo['game_permissions'] == 'all' ? : json_decode($gInfo['game_permissions'], true);
                $this ->smarty ->assign('gInfo', $gInfo);
                //if ($this ->adminfo['uid'] == 95)
                 //   print_r($gInfo['gamePermissionsList']);
            }
            $channelGroup[10000] = array('name'=>'加载首页数据');
            if($postData['sub']==1){

                    if ($postData['allGame'] == 'on')
                    {
                        $game_permissions = 'all';
                    } else {
                        if ($postData['gameList'])
                        {
                            /*
                            foreach ($postData['gameList'] as $v)
                            {
                                //$game_permissions .= $g .',';
                                $gameFromMainGameList = parent::getSameGameArrayFromGameID($v);
                                foreach ($gameFromMainGameList as $g)
                                {
                                    $game_permissions_list[$v][] = $g['gameId'];
                                }
                            }
                            */
                            $game_permissions = json_encode($postData['gameList']);
                        } else {
                            $game_permissions = 'no';
                        }
                    }

                $postData['gState'] = $postData['gState'] == 'on' ? 1 : 0;
                $postData['banBuyChannelState'] = $postData['banBuyChannelState'] == 'on' ? 1 : 0;

                $this ->model ->setTable(ADMINGROUP);
                $arr=array(
                    'gName' =>$postData['gName'],
                    'gState' =>$postData['gState'],
                    'px' =>$postData['px'],
                    'banBuyChannelState' => $postData['banBuyChannelState'],
                    'game_permissions' => $game_permissions
                );
                empty($postData['dataGroup']) && ajaxReturn('请选择数据权限！',300);
                foreach ($postData['dataGroup'] as $v){
                        $dGroup .= $v.",";
                }
                !empty($dGroup) && $arr['dGroup'] = substr($dGroup, 0,-1);
                 count($channelGroup) == count($postData['dataGroup']) && $arr['dGroup'] = 'all';
                $this ->adminfo['gId'] !=1 && $arr['maingId'] = $this ->adminfo['gId'];
                $this->model->db->begin();
                if($gInfo['gId'] > 0){
                    $arr['updUser'] = $this ->adminfo['uid'];
                    $arr['updTime'] = time();
                    $this ->model ->setKey('gId');
                    $arr['gId'] = $gInfo['gId'];
                    $r = $this ->model ->upRecordByKey($arr);
                    $r && parent::SystemDoLog('修改权限组（'.$gInfo['gName'].'）为 （'.$arr['gName'].'）！');
                }else{
                    $arr['cUser'] = $this ->adminfo['uid'];
                    $arr['cTime'] = time();

                    $r = $this ->model ->addRecord($arr);
                    $r && parent::SystemDoLog('添加权限组（'.$arr['gName'].'）！');
                }
                $this->model->db->commit();
                ajaxReturn(C('Ok:Operate'));
            }

                /**********排序游戏列表Start*********/

                $gameInfoList = array();
                $gameKey = array('abcde','fghij','klmno','pqrst','uvwxyz');
                $gameList = parent::getGameList();

                foreach ($gameKey as $k)
                {
                    foreach ($gameList as $g)
                    {
                        if ( strstr($k, strtolower( substr( $g['gameShort'],0 , 1)) ))
                        {
                            if ($g['gameId'] === $g['mainGameId']) // 按原名游戏显示
                                $gameInfoList[$k][strtolower($g['gameShort'])] = array ( 'gameName'=>$g['gameName'], 'gameId'=>$g['gameId'] );
                        }
                    }
                    ksort($gameInfoList[$k]); //排序游戏
                }
                ksort($gameInfoList);
                $this ->smarty ->assign('gameInfoList', $gameInfoList);
                /**********排序游戏列表End*********/


            if($this ->adminfo['dGroup'] !='all'){
                $dataGroup = array_keys($channelGroup);
                $dGroup_arr = explode(',', $this ->adminfo['dGroup']);
                foreach ($dataGroup as $g){
                    if(!in_array($g,$dGroup_arr)) unset ($channelGroup[$g]);
                } 
            }
            $this ->smarty ->assign('channelGroup', $channelGroup);
            $this ->smarty ->display($postData['action'].'/addGroup.html');
            exit();
        }else if($postData['api']=='del'){
            if($postData['id']==1){
                ajaxReturn(C('Error:CantDelTheGroup'), 300);
            }
            
            if($postData['id'] >1){
                $this ->model ->setTable(ADMINGROUP);
                $this ->model ->setKey('gId');
                $r = $this ->model ->delRecordByKey($postData['id']);
                $r && parent::SystemDoLog('删除权限组ID（'.$postData['id'].'）！');
                ajaxReturn(C('Ok:DeleteSub'));
            }
            ajaxReturn(C('Error:AccessError'), 300);
        }else if($postData['api']=='mult_del'){
            if(strlen($postData['ids']) >0){
                $arr = explode(',', $postData['ids']);
                $this ->model ->setTable(ADMINGROUP);
                $this ->model ->setKey('gId');
                $logId ='';
                foreach($arr as $v){
                    if($v > 1){
                        $r = $this ->model ->delRecordByKey($v);
                        $r && $logId.=$v.',';
                    }
                }
                $logId  && parent::SystemDoLog('批量删除权限组ID（'.substr($logId,0,-1).'）！');
                ajaxReturn(C('Ok:DeleteSub'));
            }
            ajaxReturn(C('Error:AccessError'), 300);
        }else if($postData['api']=='editGroupGrant'){ //分配权限
            include(DATA_DIR . 'grantlist.php');
            if($postData['id'] <1){
               ajaxReturn(C('Error:ParamError'), 300); 
            }
            $this ->model ->setTable(ADMINGROUP);
            $this ->model ->setKey('gId');
            if($postData['sub']==1){
                if($postData['id'] != 1){
                   $arr = array();
                   foreach($postData['grantlist'] as $v){
                       array_push($arr, $v, floor($v/10)*10, floor($v/100)*100, floor($v/1000)*1000);
                   }
                   $arr = array_unique($arr);
                   $t = array();
                   $t['gId'] = intval($postData['id']);
                   $t['gGrants'] = serialize($arr);
                   $t['updUser'] = $this ->adminfo['uid'];
                   $t['updTime'] = time();
                   $r = $this ->model ->upRecordByKey($t);
                   $r && parent::SystemDoLog('给权限组（ID为'.$t['gId'].'）分配权限（'.$t['gGrants'].'）！');
                   ajaxReturn(C('Ok:Update'));
                }else{
                   ajaxReturn(C('Error:CantEDITTheGroup'), 300);
                }
            }
            $gInfo = $this ->model ->getOneRecordByKey(intval($postData['id']));
            //allGrant 
            $grant = $gInfo['gGrants'] =='all'? 'all':unserialize($gInfo['gGrants']);
            $gInfo['maingId']==0 && array_push($grant,1101,1102);  //主权限默认加系统设置
            foreach ($allGrants as $key =>$v){
                if($grant =='all' || in_array($v['key'], $grant))
                    $allGrants[$key]['checked'] =1;
            }
            $mainGrant = $gInfo['maingId'] >0 ? $this ->model ->getOneRecordByKey($gInfo['maingId']):array(); 
            !empty($mainGrant) && $mainGrant_arr = unserialize($mainGrant['gGrants']);
            $data =array();
            $topMenu = $this ->getTopMenu();
            foreach($topMenu as $v){
                if(!empty($mainGrant_arr) && !in_array($v['key'], $mainGrant_arr))    continue;
                $row = array();
                $row['top'] = $v;
                $row['son'] = $this ->getLeftMenu($v,$mainGrant_arr);
                array_push($data, $row);
            }
            //if ($this ->adminfo['uid'] ==95)
               // print_r($data);
            $this ->smarty ->assign("data", $data);
            $this ->smarty ->display($postData['action'].'/editGroupGrant.html');
            exit();
        }elseif($postData['api']=='search'){ //搜索

            $this ->smarty ->display($postData['action'].'/search.html');
            exit();
        }
        $pageInfo = array('numPerPage'=>10, 'totalcount' =>0, 'currentpage' =>1);
        //处理查询
        if($postData['numPerPage'] > 0){
            $pageInfo['numPerPage'] = $postData['numPerPage'];
        }
        if($postData['currentpage'] > 1){
            $pageInfo['currentpage'] = intval($postData['currentpage']);
        }
        $where =' where 1 ';
        $this ->adminfo['gId'] !=1 && $where.=" and (maingId = ".$this->adminfo['gId']." or gId =".$this->adminfo['gId'].") ";
        if($postData['gName'] !=''){
            $where.= ' and gName like "%'.$postData['gName'].'%"';
        }
        $sql = "SELECT COUNT(*) AS total FROM ".ADMINGROUP.$where;         
        $res = $this ->model ->db ->get($sql);
        $pageInfo['totalcount'] = $res['total'];
        $pageInfo['totalpager']=  ceil($pageInfo['totalcount'] / $pageInfo['numPerPage']); //总页数
        $sql = "SELECT * FROM ".ADMINGROUP.$where." ORDER BY gId DESC LIMIT ".(($pageInfo['currentpage']-1) * $pageInfo['numPerPage']).", ".$pageInfo['numPerPage'];
        $data = $this ->model ->db ->find($sql,'gId');
        
        $sql = "SELECT gId,gName FROM ".ADMINGROUP." WHERE maingId=0 ";
        $maingArr = $this ->model ->db ->find($sql,'gId');
        
        $show=array('edit' =>1,'delete' =>1,'excel'=>1); //表格底部各功能显示，空表示可用
        $this ->adminfo['uid'] !=1 && $this->adminfo['isAdmin'] !=1 && $show['add']=1;
        $this ->smarty ->assign('data', $data);
        $this ->smarty ->assign('maingArr', $maingArr);
        $this ->smarty ->assign('show', $show);
        $this ->smarty ->assign('pageInfo', $pageInfo);
    }
    //用户管理
    public function users(){
        $postData = getRequest();
        if($postData['api']=="switch"){  //状态切换
            if(intval($postData['id']) <=0 || $this ->adminfo['gId'] !=1)
                ajaxReturn(C('Error:AccessError'), 300);  
            $this ->model ->setTable(ADMINUSERS);
            $this ->model ->setKey('uid');
            $arr=array('uLoginState' =>0,'uid'=>$postData['id']);
            intval($postData['state']) ==0 && $arr['uLoginState']=1;
            $this ->model ->upRecordByKey($arr);
            
            $r= intval($postData['state']) ==0 ? '可用':'不可用';
            parent::SystemDoLog('修改用户ID('.$arr['uid'].')状态为'.$r.' ！');
            ajaxReturn(C('Ok:Operate'));
        }
        $gwhere= " where 1 ";
        $this ->adminfo['gId'] !=1  && $gwhere.=" and (gId = ".$this ->adminfo['gId']." or maingId=".$this ->adminfo['gId'].")";
        $sql="SELECT gId,maingId, gName,gState FROM ".ADMINGROUP.$gwhere."  ORDER BY px desc,maingId asc";
        $grouplist = $this ->model ->db->find($sql,'gId');
        
        //处理api参数增删改操作
        if($postData['api']=='add'){
            if($postData['id'] > 0){
                $this ->model ->setTable(ADMINUSERS);
                $this ->model ->setKey('uid');
                $uInfo = $this ->model ->getOneRecordByKey(intval($postData['id']));
                $this ->smarty ->assign('uInfo', $uInfo);
            }
            if($postData['sub']==1){
                if($postData['uGroupId'] <=0) ajaxReturn('请选择权限组!',300);
                $this ->model ->setTable(ADMINUSERS);
                $arr=array(
                    'uName' =>$postData['uName'],
                    'uAccount' =>trim($postData['uAccount']),
                    'uGroupId' =>$postData['uGroupId'],
                    'uPhone' =>$postData['uPhone'],
                    'uMail' =>$postData['uMail'],
                    'uLoginState' =>0,
                    'isOpenPlatformB' =>0,
                    'isAdmin' =>0
                ); 
                ($postData['isAdmin'] == 'on' || $arr['uGroupId'] ==1)  && $arr['isAdmin'] =1;
                $postData['uLoginState'] == 'on'  && $arr['uLoginState'] =1;
                $postData['isOpenPlatformB'] == 'on'  && $arr['isOpenPlatformB'] =1;
                if($postData['uPass'] != ''){
                    $arr['uAttend'] = strlen($uInfo['uAttend'])==6 ? $uInfo['uAttend'] : createExt();
                    $arr['uPass'] = getPass($postData['uPass'], $arr['uAttend']);
                }
                if($uInfo['uid'] > 0){
                    $this ->adminfo['gId'] !=1 &&  $this->adminfo['isAdmin'] !=1 && ajaxReturn("你的权限不够!",300);
                    $this ->model ->setKey('uid');
                    $arr['uid'] = $uInfo['uid'];
                    $r = $this ->model ->upRecordByKey($arr); 
                    $r && parent::SystemDoLog('修改用户ID('.$arr['uid'].')信息！');
                }else{
                    $this ->model ->setKey('uAccount');
                    if($this ->model ->getOneRecordByKey($arr['uAccount']))
                        ajaxReturn("账号：".$arr['uAccount']." 已存在！",300);
                    $r = $this ->model ->addRecord($arr);
                    $r && parent::SystemDoLog('添加用户('.$arr['uName'].')账号！');
                }
                file_get_contents(URL_SUPER . 'api/updateAdminUser.php');
                ajaxReturn(C('Ok:Operate'));
            }
            
            $this ->smarty ->assign('grouplist', $grouplist);
            $this ->smarty ->display($postData['action'].'/addUser.html');
            exit();
        }else if($postData['api']=='del'){
            if($this ->adminfo['uid'] ==$postData['id']){
                ajaxReturn("开玩笑删除自己账号！",300);
            }
            if($postData['id']==1){
                ajaxReturn(C('Error:CantDelTheUser'), 300);
            }
            $this ->adminfo['isAdmin'] !=1 && ajaxReturn("你的权限不够!",300);
            if($this ->adminfo['gId'] !=1){
                $sql=" select maingId from ".ADMINUSERS." where gId=".$postData['id'];
                $g =$this ->model ->db ->get($sql);
                if(!$g || $g['maingId']!=$this ->adminfo['gId'])
                     ajaxReturn("不是您的子权限组不能删!",300);
            }
            if($postData['id'] >1){
                $this ->model ->setTable(ADMINUSERS);
                $this ->model ->setKey('uid');
                $r = $this ->model ->delRecordByKey($postData['id']);
                $r && parent::SystemDoLog('删除用户ID('.$postData['id'].')！');
                ajaxReturn(C('Ok:DeleteSub'));
            }
            ajaxReturn(C('Error:AccessError'), 300);
        }else if($postData['api']=='mult_del'){
            $this ->adminfo['isAdmin'] !=1 && ajaxReturn("你的权限不够!",300);
            $logs ='';
            if(strlen($postData['ids']) >0){
                $arr = explode(',', $postData['ids']);
                $this ->model ->setTable(ADMINUSERS);
                $this ->model ->setKey('uid');
                foreach($arr as $v){
                    if($this ->adminfo['gId'] !=1){
                        $sql=" select maingId from ".ADMINUSERS." where gId=".$v;
                        $g =$this ->model ->db ->get($sql);
                        if(!$g || $g['maingId']!=$this ->adminfo['gId'])
                            continue;
                    }
                    $r = $this ->model ->delRecordByKey($v);
                    $r && $logs.=$v.',';
                }
                
                $logs && parent::SystemDoLog('批量删除用户ID('.substr($logs,0,-1).')！');
                ajaxReturn(C('Ok:DeleteSub'));
            }
            ajaxReturn(C('Error:AccessError'), 300);
        }elseif($postData['api']=='search'){ //搜索
            
            $this ->smarty ->display($postData['action'].'/search.html');
            exit();
        }
    //给后台账号发放平台币    
        elseif ($postData['api'] == 'addPlatformB'){ 
            $postData['id'] = intval($postData['id']) + 0;
            $this ->model ->setTable(ADMINUSERS);
            $this ->model ->setKey('uid');
            $data = $this ->model ->getOneRecordByKey($this ->adminfo['uid']);
            
            if($postData['sub']==1){
                $this ->adminfo['uid'] !=47 && ajaxReturn("你没有权限！",300);
                if ($data['addPlatformPwd']) {
                    $postData['payPassword'] = trim($postData['payPassword']);
                    $pay_pwd = getPass($postData['payPassword'], $this ->adminfo['passext']);
                    $pay_pwd != $data['addPlatformPwd'] && ajaxReturn(C('Error:PassError'), 300);
                }
                $postData['remark'] = mysql_escape_string(getStr($postData['remark']));
                $postData['discount'] = intval($postData['discount']) + 0;
                $postData['discount'] > 100 && $postData['discount'] <= 0 && ajaxReturn(C('Error:ParamNotIsNull'), 300);
                $true_discount = round($postData['discount'] / 100, 2);

                $postData['addPlatformB'] = intval($postData['addPlatformB']) + 0;
                $postData['payMoney'] = intval($postData['payMoney']) + 0;
                
                $postData['addPlatformB'] != round($postData['payMoney'] / $true_discount) && ajaxReturn(C('Error:ParamError'), 300);

                $arr=array(
                    'uid' => $this ->adminfo['uid'],
                    'addUid' =>$postData['id'],
                    'platformB' => $postData['addPlatformB'],
                    'discount' => $true_discount,
                    'money' => $postData['payMoney'],
                    'payTime' => $_SERVER['REQUEST_TIME'],
                    'remark' => $postData['remark']
                );
                $this->model->db->begin();
                $return_state = $this->model->db->save(ADMINADDPLATFORMBLOG, $arr);
                
                if (! $return_state) {
                    $this->model->db->rollback();
                    ajaxReturn(C('Error:OprateFail'), 300);
                }

                $this->model->db->query("UPDATE `" . ADMINUSERS . "` SET `platformB`=`platformB`+{$postData['addPlatformB']} WHERE `uid`={$postData['id']} LIMIT 1");
                $return_state = $this->model->db->affected_rows();
                if ($return_state <= 0) {
                    $this->model->db->rollback();
                    ajaxReturn(C('Error:OprateFail'), 300);
                }
                parent::SystemDoLog('成功给用户ID('.$postData['id'].')充值 ('.$postData['addPlatformB'].')平台币！');
                $this->model->db->commit();
                ajaxReturn(C('Ok:Operate'));
            }
            if($postData['id'] > 0){
                $this ->model ->setTable(ADMINUSERS);
                $this ->model ->setKey('uid');
                $gInfo = $this ->model ->getOneRecordByKey($postData['id']);
                $discount_arr = $this->model->db->get("SELECT `discount` FROM " . ADMINADDPLATFORMBLOG . " WHERE `addUid`={$postData['id']} ORDER BY `payTime` DESC LIMIT 1");
                $gInfo['discount'] = round($discount_arr['discount'] * 100);
                $this ->smarty ->assign('gInfo', $gInfo);
            }
            $this ->smarty ->assign("data",$data);
            $this ->smarty ->display($postData['action'].'/addPlatformB.html');
            exit();
        }elseif ($postData['api'] == 'addVoucher'){  //放发代金券
            $postData['id'] = intval($postData['id']) + 0;
            $this ->model ->setTable(ADMINUSERS);
            $this ->model ->setKey('uid');
            $data = $this ->model ->getOneRecordByKey($this ->adminfo['uid']);
            $this ->smarty ->assign('data', $data);
            if($postData['id'] > 0){
                $gInfo = $this ->model ->getOneRecordByKey($postData['id']);
                $this ->smarty ->assign('gInfo', $gInfo);
            }
            
            if($postData['sub']==1){
                $this ->adminfo['uid'] !=47 && ajaxReturn("你没有权限！",300);   // 47
                if ($data['addPlatformPwd']) {
                    $postData['payPassword'] = trim($postData['payPassword']);
                    $pay_pwd = getPass($postData['payPassword'], $this ->adminfo['passext']);
                    $pay_pwd != $data['addPlatformPwd'] && ajaxReturn(C('Error:PassError'), 300);
                }
                $postData['remark'] = mysql_escape_string(getStr($postData['remark']));
                $postData['discount'] = 100; //暂且默认为100
                $postData['discount'] > 100 && $postData['discount'] <= 0 && ajaxReturn(C('Error:ParamNotIsNull'), 300);
                $true_discount = round($postData['discount'] / 100, 2);

                $postData['voucher'] = round($postData['voucher'],2); //充值代金券
                $postData['payMoney'] = round($postData['payMoney'],2); //收到RMB
                 
                $postData['voucher'] != round($postData['payMoney'] / $true_discount,2) && ajaxReturn(C('Error:ParamError'), 300);

                $arr=array(
                    'uid' => $this ->adminfo['uid'],
                    'addUid' =>$postData['id'],
                    'voucher' => $postData['voucher'],
                    'discount' => $true_discount,
                    'money' => $postData['payMoney'],
                    'payTime' => $_SERVER['REQUEST_TIME'],
                    'remark' => $postData['remark']
                );
                $this->model->db->begin();
                $return_state = $this->model->db->save(ADMINADDVOUCHERLOG, $arr);
                
                if (! $return_state) {
                    $this->model->db->rollback();
                    ajaxReturn(C('Error:OprateFail'), 300);
                }

                $this->model->db->query("UPDATE `" . ADMINUSERS . "` SET `voucher`=`voucher`+{$postData['voucher']} WHERE `uid`={$postData['id']} LIMIT 1");
                $return_state = $this->model->db->affected_rows();
                if ($return_state <= 0) {
                    $this->model->db->rollback();
                    ajaxReturn(C('Error:OprateFail'), 300);
                }
                parent::SystemDoLog('成功给用户ID('.$postData['id'].')充值 ('.$postData['voucher'].')代金券！');
                $this->model->db->commit();
                ajaxReturn(C('Ok:Operate'));
            }
            
            $this ->smarty ->display($postData['action'].'/addVoucher.html');
            exit();
        }

        //处理查询
        $pageInfo = array('numPerPage'=>10, 'totalcount' =>0, 'currentpage' =>1);
        //处理查询
        if($postData['numPerPage'] > 0){
            $pageInfo['numPerPage'] = $postData['numPerPage'];
        }
        if($postData['currentpage'] > 1){
            $pageInfo['currentpage'] = intval($postData['currentpage']);
        }
        $where =' WHERE 1 ';
        
        if($this ->adminfo['gId'] !=1){
            $g_str =$this ->adminfo['gId'].",";
            foreach ($grouplist as $g){
                $g['maingId']==$this ->adminfo['gId'] && $g_str.=$g['gId'].",";
            }
            !empty($g_str) && $where.=" AND uGroupId IN (".substr($g_str, 0,-1).")";
        }
        if($postData['gName'] !=''){
            $where.= ' AND  (uName like "%'.trim($postData['gName']).'%" OR uAccount like "%'.trim($postData['gName']).'%")';
        }
        $sql = "SELECT COUNT(*) AS total FROM ".ADMINUSERS.$where;  
        $res = $this ->model ->db ->get($sql);
        $pageInfo['totalcount'] = $res['total'];
        $pageInfo['totalpager']=  ceil($pageInfo['totalcount'] / $pageInfo['numPerPage']); //总页数
        $sql = "SELECT * FROM ".ADMINUSERS.$where." ORDER BY uid DESC LIMIT ".(($pageInfo['currentpage']-1) * $pageInfo['numPerPage']).", ".$pageInfo['numPerPage'];
        $data = $this ->model ->db ->find($sql);
        foreach ($grouplist as $v){
            $grouplistArr[$v['gId']] = $v;
        }
        $show=array("excel"=>1); //表格底部各功能显示，空表示可用
        $this ->adminfo['isAdmin'] !=1 &&  $show=array('add'=>1,'edit' =>1,'excel' =>1,'delete' =>1,'search'=>1); 
        $this ->smarty ->assign('data', $data);
        $this ->smarty ->assign('show', $show);
        $this ->smarty ->assign('pageInfo', $pageInfo);
        $this ->smarty ->assign('grouplist', $grouplistArr);
    }
  
    //==============================私有函數=========================    
    //获取指定顶部模块对应左边菜单
    private function getLeftMenu($topInfo,$mainGroup = array()){
        global $allGrants;
        $leftMenu = array();
        foreach($allGrants as $val){
            if(!empty($mainGroup) && !in_array($val['key'],$mainGroup))  continue;
            if(floor(($val['key'] - $topInfo['key'])/1000) == 0 && $val['key'] != $topInfo['key'])
                array_push($leftMenu, $val);
        }
        $data = $this ->divideGrade($leftMenu,$mainGroup);
        return $data;
    }
    //分级
    private function divideGrade($userLeftMenu, $mainGroup = array()){
        $data = array();
         foreach($userLeftMenu as $val){
            if(fmod(($val['key']), 100) == 0){
                foreach($userLeftMenu as $v){
                    if(!empty($mainGroup) && !in_array($v['key'],$mainGroup))  continue;
                    $c = $v['key'] - $val['key'];
                    if(($c) >0 && ($c) <20)
                       $val['sonfunc'][$v['key']] = $v;
                    if(($c) >=20 && ($c) <100)
                        if(fmod($c, 20) == 0)
                            $val['sondir'][$v['key']] = $v;
                        else
                            $val['sondir'][floor($v['key']/20)*10]['sonfunc'][] = $v;
                }
                $data[$val['key']] = $val;
            }
        }
        return $data;
    }
    //获取顶部导航列表
    private function getTopMenu(){
        global $allGrants;
        $topMenu = array();
        foreach($allGrants as $val){
            if(fmod($val['key'], 1000)==0)
                array_push($topMenu, $val);
        }
        return $topMenu;
    }
//==============================================================================================================================================
    public function systemlogss(){
        $postData = getRequest();
        if($postData['api']=="switch"){  //状态切换
            if(intval($postData['id']) <=0 || $this ->adminfo['gId'] !=1)
                ajaxReturn(C('Error:AccessError'), 300);
            $this ->model ->setTable(ADMINUSERS);
            $this ->model ->setKey('uid');
            $arr=array('uLoginState' =>0,'uid'=>$postData['id']);
            intval($postData['state']) ==0 && $arr['uLoginState']=1;
            $this ->model ->upRecordByKey($arr);

            $r= intval($postData['state']) ==0 ? '可用':'不可用';
            parent::SystemDoLog('修改用户ID('.$arr['uid'].')状态为'.$r.' ！');
            ajaxReturn(C('Ok:Operate'));
        }
        $gwhere= " where 1 ";
        $this ->adminfo['gId'] !=1  && $gwhere.=" and (gId = ".$this ->adminfo['gId']." or maingId=".$this ->adminfo['gId'].")";
        $sql="SELECT gId,maingId, gName,gState FROM ".ADMINGROUP.$gwhere."  ORDER BY px desc,maingId asc";
        $grouplist = $this ->model ->db->find($sql,'gId');

        //处理api参数增删改操作
        if($postData['api']=='add'){
            if($postData['id'] > 0){
                $this ->model ->setTable(ADMINUSERS);
                $this ->model ->setKey('uid');
                $uInfo = $this ->model ->getOneRecordByKey(intval($postData['id']));
                $this ->smarty ->assign('uInfo', $uInfo);
            }
            if($postData['sub']==1){
                if($postData['uGroupId'] <=0) ajaxReturn('请选择权限组!',300);
                $this ->model ->setTable(ADMINUSERS);
                $arr=array(
                    'uName' =>$postData['uName'],
                    'uAccount' =>trim($postData['uAccount']),
                    'uGroupId' =>$postData['uGroupId'],
                    'uPhone' =>$postData['uPhone'],
                    'uMail' =>$postData['uMail'],
                    'uLoginState' =>0,
                    'isOpenPlatformB' =>0,
                    'isAdmin' =>0
                );
                ($postData['isAdmin'] == 'on' || $arr['uGroupId'] ==1)  && $arr['isAdmin'] =1;
                $postData['uLoginState'] == 'on'  && $arr['uLoginState'] =1;
                $postData['isOpenPlatformB'] == 'on'  && $arr['isOpenPlatformB'] =1;
                if($postData['uPass'] != ''){
                    $arr['uAttend'] = strlen($uInfo['uAttend'])==6 ? $uInfo['uAttend'] : createExt();
                    $arr['uPass'] = getPass($postData['uPass'], $arr['uAttend']);
                }
                if($uInfo['uid'] > 0){
                    $this ->adminfo['gId'] !=1 &&  $this->adminfo['isAdmin'] !=1 && ajaxReturn("你的权限不够!",300);
                    $this ->model ->setKey('uid');
                    $arr['uid'] = $uInfo['uid'];
                    $r = $this ->model ->upRecordByKey($arr);
                    $r && parent::SystemDoLog('修改用户ID('.$arr['uid'].')信息！');
                }else{
                    $this ->model ->setKey('uAccount');
                    if($this ->model ->getOneRecordByKey($arr['uAccount']))
                        ajaxReturn("账号：".$arr['uAccount']." 已存在！",300);
                    $r = $this ->model ->addRecord($arr);
                    $r && parent::SystemDoLog('添加用户('.$arr['uName'].')账号！');
                }
                file_get_contents(URL_SUPER . 'api/updateAdminUser.php');
                ajaxReturn(C('Ok:Operate'));
            }

            $this ->smarty ->assign('grouplist', $grouplist);
            $this ->smarty ->display($postData['action'].'/addUser.html');
            exit();
        }else if($postData['api']=='del'){
            if($this ->adminfo['uid'] ==$postData['id']){
                ajaxReturn("开玩笑删除自己账号！",300);
            }
            if($postData['id']==1){
                ajaxReturn(C('Error:CantDelTheUser'), 300);
            }
            $this ->adminfo['isAdmin'] !=1 && ajaxReturn("你的权限不够!",300);
            if($this ->adminfo['gId'] !=1){
                $sql=" select maingId from ".ADMINUSERS." where gId=".$postData['id'];
                $g =$this ->model ->db ->get($sql);
                if(!$g || $g['maingId']!=$this ->adminfo['gId'])
                    ajaxReturn("不是您的子权限组不能删!",300);
            }
            if($postData['id'] >1){
                $this ->model ->setTable(ADMINUSERS);
                $this ->model ->setKey('uid');
                $r = $this ->model ->delRecordByKey($postData['id']);
                $r && parent::SystemDoLog('删除用户ID('.$postData['id'].')！');
                ajaxReturn(C('Ok:DeleteSub'));
            }
            ajaxReturn(C('Error:AccessError'), 300);
        }else if($postData['api']=='mult_del'){
            $this ->adminfo['isAdmin'] !=1 && ajaxReturn("你的权限不够!",300);
            $logs ='';
            if(strlen($postData['ids']) >0){
                $arr = explode(',', $postData['ids']);
                $this ->model ->setTable(ADMINUSERS);
                $this ->model ->setKey('uid');
                foreach($arr as $v){
                    if($this ->adminfo['gId'] !=1){
                        $sql=" select maingId from ".ADMINUSERS." where gId=".$v;
                        $g =$this ->model ->db ->get($sql);
                        if(!$g || $g['maingId']!=$this ->adminfo['gId'])
                            continue;
                    }
                    $r = $this ->model ->delRecordByKey($v);
                    $r && $logs.=$v.',';
                }

                $logs && parent::SystemDoLog('批量删除用户ID('.substr($logs,0,-1).')！');
                ajaxReturn(C('Ok:DeleteSub'));
            }
            ajaxReturn(C('Error:AccessError'), 300);
        }elseif($postData['api']=='search'){ //搜索

            $this ->smarty ->display($postData['action'].'/search.html');
            exit();
        }
        //给后台账号发放平台币
        elseif ($postData['api'] == 'addPlatformB'){
            $postData['id'] = intval($postData['id']) + 0;
            $this ->model ->setTable(ADMINUSERS);
            $this ->model ->setKey('uid');
            $data = $this ->model ->getOneRecordByKey($this ->adminfo['uid']);

            if($postData['sub']==1){
                $this ->adminfo['uid'] !=47 && ajaxReturn("你没有权限！",300);
                if ($data['addPlatformPwd']) {
                    $postData['payPassword'] = trim($postData['payPassword']);
                    $pay_pwd = getPass($postData['payPassword'], $this ->adminfo['passext']);
                    $pay_pwd != $data['addPlatformPwd'] && ajaxReturn(C('Error:PassError'), 300);
                }
                $postData['remark'] = mysql_escape_string(getStr($postData['remark']));
                $postData['discount'] = intval($postData['discount']) + 0;
                $postData['discount'] > 100 && $postData['discount'] <= 0 && ajaxReturn(C('Error:ParamNotIsNull'), 300);
                $true_discount = round($postData['discount'] / 100, 2);

                $postData['addPlatformB'] = intval($postData['addPlatformB']) + 0;
                $postData['payMoney'] = intval($postData['payMoney']) + 0;

                $postData['addPlatformB'] != round($postData['payMoney'] / $true_discount) && ajaxReturn(C('Error:ParamError'), 300);

                $arr=array(
                    'uid' => $this ->adminfo['uid'],
                    'addUid' =>$postData['id'],
                    'platformB' => $postData['addPlatformB'],
                    'discount' => $true_discount,
                    'money' => $postData['payMoney'],
                    'payTime' => $_SERVER['REQUEST_TIME'],
                    'remark' => $postData['remark']
                );
                $this->model->db->begin();
                $return_state = $this->model->db->save(ADMINADDPLATFORMBLOG, $arr);

                if (! $return_state) {
                    $this->model->db->rollback();
                    ajaxReturn(C('Error:OprateFail'), 300);
                }

                $this->model->db->query("UPDATE `" . ADMINUSERS . "` SET `platformB`=`platformB`+{$postData['addPlatformB']} WHERE `uid`={$postData['id']} LIMIT 1");
                $return_state = $this->model->db->affected_rows();
                if ($return_state <= 0) {
                    $this->model->db->rollback();
                    ajaxReturn(C('Error:OprateFail'), 300);
                }
                parent::SystemDoLog('成功给用户ID('.$postData['id'].')充值 ('.$postData['addPlatformB'].')平台币！');
                $this->model->db->commit();
                ajaxReturn(C('Ok:Operate'));
            }
            if($postData['id'] > 0){
                $this ->model ->setTable(ADMINUSERS);
                $this ->model ->setKey('uid');
                $gInfo = $this ->model ->getOneRecordByKey($postData['id']);
                $discount_arr = $this->model->db->get("SELECT `discount` FROM " . ADMINADDPLATFORMBLOG . " WHERE `addUid`={$postData['id']} ORDER BY `payTime` DESC LIMIT 1");
                $gInfo['discount'] = round($discount_arr['discount'] * 100);
                $this ->smarty ->assign('gInfo', $gInfo);
            }
            $this ->smarty ->assign("data",$data);
            $this ->smarty ->display($postData['action'].'/addPlatformB.html');
            exit();
        }elseif ($postData['api'] == 'addVoucher'){  //放发代金券
            $postData['id'] = intval($postData['id']) + 0;
            $this ->model ->setTable(ADMINUSERS);
            $this ->model ->setKey('uid');
            $data = $this ->model ->getOneRecordByKey($this ->adminfo['uid']);
            $this ->smarty ->assign('data', $data);
            if($postData['id'] > 0){
                $gInfo = $this ->model ->getOneRecordByKey($postData['id']);
                $this ->smarty ->assign('gInfo', $gInfo);
            }

            if($postData['sub']==1){
                $this ->adminfo['uid'] !=47 && ajaxReturn("你没有权限！",300);   // 47
                if ($data['addPlatformPwd']) {
                    $postData['payPassword'] = trim($postData['payPassword']);
                    $pay_pwd = getPass($postData['payPassword'], $this ->adminfo['passext']);
                    $pay_pwd != $data['addPlatformPwd'] && ajaxReturn(C('Error:PassError'), 300);
                }
                $postData['remark'] = mysql_escape_string(getStr($postData['remark']));
                $postData['discount'] = 100; //暂且默认为100
                $postData['discount'] > 100 && $postData['discount'] <= 0 && ajaxReturn(C('Error:ParamNotIsNull'), 300);
                $true_discount = round($postData['discount'] / 100, 2);

                $postData['voucher'] = round($postData['voucher'],2); //充值代金券
                $postData['payMoney'] = round($postData['payMoney'],2); //收到RMB

                $postData['voucher'] != round($postData['payMoney'] / $true_discount,2) && ajaxReturn(C('Error:ParamError'), 300);

                $arr=array(
                    'uid' => $this ->adminfo['uid'],
                    'addUid' =>$postData['id'],
                    'voucher' => $postData['voucher'],
                    'discount' => $true_discount,
                    'money' => $postData['payMoney'],
                    'payTime' => $_SERVER['REQUEST_TIME'],
                    'remark' => $postData['remark']
                );
                $this->model->db->begin();
                $return_state = $this->model->db->save(ADMINADDVOUCHERLOG, $arr);

                if (! $return_state) {
                    $this->model->db->rollback();
                    ajaxReturn(C('Error:OprateFail'), 300);
                }

                $this->model->db->query("UPDATE `" . ADMINUSERS . "` SET `voucher`=`voucher`+{$postData['voucher']} WHERE `uid`={$postData['id']} LIMIT 1");
                $return_state = $this->model->db->affected_rows();
                if ($return_state <= 0) {
                    $this->model->db->rollback();
                    ajaxReturn(C('Error:OprateFail'), 300);
                }
                parent::SystemDoLog('成功给用户ID('.$postData['id'].')充值 ('.$postData['voucher'].')代金券！');
                $this->model->db->commit();
                ajaxReturn(C('Ok:Operate'));
            }

            $this ->smarty ->display($postData['action'].'/addVoucher.html');
            exit();
        }

        //处理查询
        $pageInfo = array('numPerPage'=>10, 'totalcount' =>0, 'currentpage' =>1);
        //处理查询
        if($postData['numPerPage'] > 0){
            $pageInfo['numPerPage'] = $postData['numPerPage'];
        }
        if($postData['currentpage'] > 1){
            $pageInfo['currentpage'] = intval($postData['currentpage']);
        }
        $where =' WHERE 1 ';

        if($this ->adminfo['gId'] !=1){
            $g_str =$this ->adminfo['gId'].",";
            foreach ($grouplist as $g){
                $g['maingId']==$this ->adminfo['gId'] && $g_str.=$g['gId'].",";
            }
            !empty($g_str) && $where.=" AND uGroupId IN (".substr($g_str, 0,-1).")";
        }
        if($postData['gName'] !=''){
            $where.= ' AND  (uName like "%'.trim($postData['gName']).'%" OR uAccount like "%'.trim($postData['gName']).'%")';
        }
        $sql = "SELECT COUNT(*) AS total FROM ".ADMINUSERS.$where;
        $res = $this ->model ->db ->get($sql);
        $pageInfo['totalcount'] = $res['total'];
        $pageInfo['totalpager']=  ceil($pageInfo['totalcount'] / $pageInfo['numPerPage']); //总页数
        $sql = "SELECT * FROM ".ADMINUSERS.$where." ORDER BY uid DESC LIMIT ".(($pageInfo['currentpage']-1) * $pageInfo['numPerPage']).", ".$pageInfo['numPerPage'];
        $data = $this ->model ->db ->find($sql);
        foreach ($grouplist as $v){
            $grouplistArr[$v['gId']] = $v;
        }
        $show=array("excel"=>1); //表格底部各功能显示，空表示可用
        $this ->adminfo['isAdmin'] !=1 &&  $show=array('add'=>1,'edit' =>1,'excel' =>1,'delete' =>1,'search'=>1);
        $this ->smarty ->assign('data', $data);
        $this ->smarty ->assign('show', $show);
        $this ->smarty ->assign('pageInfo', $pageInfo);
        $this ->smarty ->assign('grouplist', $grouplistArr);
    }

//----------------------------------------------------------------------------------------------------
    public function  systemlog()
    {
        $postData = getRequest();
        $gwhere= " where 1 ";
        $this ->adminfo['Id'] !=1  && $gwhere.=" and (Id = ".$this ->adminfo['Id']." or uId=".$this ->adminfo['Id'].")";
        $sql="SELECT * FROM ".SYSTEMLOG."  ORDER BY id desc,uid asc";
        $grouplist = $this ->model ->db->find($sql,'id');
        $pageInfo = array('numPerPage'=>10, 'totalcount' =>0, 'currentpage' =>1);
        //处理查询
        if($postData['numPerPage'] > 0){
            $pageInfo['numPerPage'] = $postData['numPerPage'];
        }
        if($postData['currentpage'] > 1){
            $pageInfo['currentpage'] = intval($postData['currentpage']);
        }

        $sql = "SELECT COUNT(*) AS total FROM ".SYSTEMLOG;//获取表内的数据总行数 并封入total中
        $res = $this ->model ->db ->get($sql);//记录总行数
        $pageInfo['totalcount'] = $res['total'];//总行数
        $pageInfo['totalpager']=  ceil($pageInfo['totalcount'] / $pageInfo['numPerPage']); //总页数
        $sql = "SELECT * FROM ".SYSTEMLOG." ORDER BY id DESC LIMIT ".(($pageInfo['currentpage']-1) * $pageInfo['numPerPage']).", ".$pageInfo['numPerPage'];// 进行分页操作
        $data = $this ->model ->db ->find($sql);
        foreach ($grouplist as $v){
            $grouplistArr[$v['id']] = $v;
        }
        //添加系统日志==========================================================================================================

       // $this->add_system_log('');
        //============================================================================================
        $show=array("excel"=>1); //表格底部各功能显示，空表示可用
        $this ->adminfo['isAdmin'] !=1 &&  $show=array('add'=>1,'edit' =>1,'excel' =>1,'delete' =>1,'search'=>1);
        $this ->smarty ->assign('data', $data);
        $this ->smarty ->assign('show', $show);
        $this ->smarty ->assign('pageInfo', $pageInfo);
        $this ->smarty ->assign('grouplist', $grouplistArr);


    }
}


