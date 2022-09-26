<?php
/**
 * Created by PhpStorm.
 * User: fengpengjun
 * Date: 2019/6/6
 * Time: 14:11
 */
ini_set('display_errors','Off');
error_reporting(E_ALL);

include_once "WXBizMsgCrypt.php";

class WorkApi{

    private $textTpl = "<xml>
                   <ToUserName><![CDATA[%s]]></ToUserName>
                   <FromUserName><![CDATA[%s]]></FromUserName> 
                   <CreateTime>%s</CreateTime>
                   <MsgType><![CDATA[%s]]></MsgType>
                   <Content><![CDATA[%s]]></Content>
                </xml>";

    public function __construct() {
        $this->corpId = 'ww4ad764c3c1XXXXX';
        $this->token = 'KV7DxCtuOsS8GrDXXXXX';
        $this->encodingAesKey = 'DnUujNA863ts5DRkPhFEXzhASbcpGhIS0GukxbXXXXX';
        $this->wxcpt = new WXBizMsgCrypt($this->token, $this->encodingAesKey, $this->corpId);

    }
    //valid方法代表验证接口
    public function checkValid(){
        $sVerifyMsgSig = $_GET['msg_signature'];
        $sVerifyTimeStamp= $_GET['timestamp'];
        $sVerifyNonce= $_GET['nonce'];
        $sVerifyEchoStr= $_GET['echostr'];
        $sEchoStr = "";
        $errCode = $this->wxcpt->VerifyURL($sVerifyMsgSig, $sVerifyTimeStamp, $sVerifyNonce, $sVerifyEchoStr, $sEchoStr);
        file_put_contents('/tmp/work.php',date("Y-m-d H:i:s").' checkCode: '.$sEchoStr."\n",FILE_APPEND);
        if ($errCode == 0) {
            return $sEchoStr;
        } else {
            return $errCode;
        }
    }
    public function reponseMsg(){
        $sReqData = file_get_contents("php://input");
        $sReqMsgSig = $_GET['msg_signature'];
        $sReqTimeStamp= $_GET['timestamp'];
        $sReqNonce= $_GET['nonce'];
        $sMsg = "";  // 解析之后的明文
        file_put_contents('/tmp/work.php',date("Y-m-d H:i:s")." origin_data:: ".json_encode($_GET)."\n",FILE_APPEND);
        //解密
        file_put_contents('/tmp/work.php',date("Y-m-d H:i:s")
            .' 原始的xml: '.$sReqData
            ."\n",FILE_APPEND);
        $errCode = $this->wxcpt->DecryptMsg($sReqMsgSig, $sReqTimeStamp, $sReqNonce, $sReqData, $sMsg);
        file_put_contents('/tmp/work.php',date("Y-m-d H:i:s")
            .' 解密的code: '.$errCode
            ."\n",FILE_APPEND);

        if ($errCode != 0) {
            return -1;
        }
        file_put_contents('/tmp/work.php',date("Y-m-d H:i:s")
            .' 解密之后的xml: '.$sMsg
            ."\n",FILE_APPEND);
        libxml_disable_entity_loader(true);
        //把XML数据转换为$postObj对象
        $postObj = simplexml_load_string($sMsg, 'SimpleXMLElement', LIBXML_NOCDATA);
        //获取接收到的数据信息
        $fromUsername = $postObj->FromUserName;
        $toUsername = $postObj->ToUserName;
        //获取MsgType属性
        $msgType = $postObj->MsgType; //严格区分大小写的
        //获取语音识别后的结果
        $rec = $postObj->Recognition;
        //应用id
        $agentId = $postObj->AgentID;
        //消息Id
        $msgId = $postObj->MsgId;
        $keyword = trim($postObj->Content);
        $time = time();
        file_put_contents('/tmp/work.php',date("Y-m-d H:i:s")
            .' 来自谁: '.$fromUsername."\n"
            .' 发给谁: '.$toUsername."\n"
            .' 谁发的: '.$toUsername."\n"
            .' 收到的内容类型是: '.$msgType."\n"
            .' 收到的内容关键字是: '.$keyword."\n"
            .' 应用id: '.$agentId."\n"
            .' 消息id: '.$msgId."\n"
            ,FILE_APPEND);
        switch ($msgType){
            //文本消息
            case 'text':
                $msgContent = '我来也！????';
                // $msgContent = $keyword;

                
                $sReplyMsg = sprintf($this->textTpl, $fromUsername, $toUsername, $time, $msgType, $msgContent);
                $sEncryptMsg = ""; //xml格式的密文
                file_put_contents('/tmp/work.php',date("Y-m-d H:i:s")
                    .' 要发送的内容是的的内容是: '.$sReplyMsg."\n"
                    .' 发给谁: '.$toUsername."\n"
                    .' 谁发的: '.$fromUsername."\n"
                    .' 要发送的内容类型是: '.$msgType."\n"
                    .' 应用id: '.$agentId."\n"
                    .' 消息id: '.$msgId."\n"
                    ,FILE_APPEND);
                $errCode = $this->wxcpt->EncryptMsg($sReplyMsg, $sReqTimeStamp, $sReqNonce, $sEncryptMsg);

                file_put_contents('/tmp/work.php',date("Y-m-d H:i:s")
                    .' 加密后的消息: '.$sEncryptMsg
                    ."\n",FILE_APPEND);
                if($errCode != 0){
                    return -1;
                }
                return $sEncryptMsg;
                break;
            // 图片消息
            case 'image':
                break;
            //语音消息
            case 'voice':
                break;
            //图文消息
            case 'news':
                break;

        }
    }
}

$obj = new WorkApi();
if(isset($_GET['echostr']) && $_GET['echostr']){//验证url的有效性
   $msg = $obj->checkValid();
   if($msg >0){
       echo $msg;
   }
}else{
    $msg = $obj->reponseMsg();
    echo $msg;
}
