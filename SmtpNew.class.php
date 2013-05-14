<?php 
/**
 * @FileName    SmtpNew.class.php
 * Smtp服务邮件封装
 *
 * @Copyright  (c) 1998-2013 All Rights Reserved
 * @Author      sanwhite  <yyy@iam3y.com>
 * @Version  	$Id$
 */

//新版邮件服务器
define("SMTP_HOSTNAME", "your smtp hostname");
define("SMTP_SERVER", "your smtp ip");
define("SMTP_PORT", 25);
define("SMTP_CHARSET", "GB2312");
define("SMTP_TIMEOUT", 20);
define("SMTP_USERNAME", "the username to log on the smtp server");
define("SMTP_PASSWORD", "the pwd of username to log on the smtp server");
define("CHARSET", "GB2312");

/**
 * 新版Smtp服务邮件封装,初始化一个类，请使用 SmtpNew::init()
 */
class SmtpNew{
	
	/**stmp资源句柄**/
	private $smtp_fp = null;
	
	/**是否是附件邮件**/
	private $isatt = false;
	
	/**是否是html邮件**/
	private $ishtml = false;
	
	/**邮件编码**/
	private $charset = "GB2312";
	
	/**邮件头**/
	private $header = "";
	
	/**邮件主体**/
	private $body = "";
	
	/**邮件主题**/
	private $subject = "";
	
	/**邮件发件人**/
	private $from = "";
	
	/**邮件收件人**/
	private $to = array();
	
	/**邮件抄送人**/
	private $cc = array();
	
	/**邮件密送人**/
	private $bcc = array();
	
	/**附件**/
	private $attachments = array();
	
	/**边界**/
	private $boundary = array();
	
	/**返回数据**/
	private $return_data = array(
							'code'=>false,
							'msg'=>'',
							'status'=>array(
										'to'=>array(
												'dolist'=>'',
												'undolist'=>''),
										'cc'=>array(
												'dolist'=>'',
												'undolist'=>'')
							));
	
	/**
	 * 初始化连接
	 */
	static function init($errno=0,$errstr=''){
		
		$o = new self();
		$o->smtp_fp = @fsockopen( SMTP_SERVER, SMTP_PORT, $errno, $errstr, SMTP_TIMEOUT );
		
		if(!$o->smtp_fp){
			return $o->return_data;
		}
		
		return $o;
	}
	
	/**
	 * 是否是附件邮件
	 * @param bool $isatt
	 * 
	 * @example:使用示例
	 * <code>
	 * $smtp = new SmtpNew::init();
	 * $smtp->isAttachmentEmail(true);
	 * </code>
	 */
	public function isAttachmentEmail($isatt){
		
		$this->isatt = $isatt;
		$boundary_1 = md5(rand(10000, 99999));
		$boundary_2 = md5(rand(10000, 99999)."_1");//generate the boundarys
		$this->boundary[0] = $boundary_1;
		$this->boundary[1] = $boundary_2;
	}
	
	/**
   * do u want to send a mail with html code?
   * 
  **/
	public function ishtml($ishtml){
		
		$this->ishtml = $ishtml;
	}
	
	
	/**
	 * 设置发送人
	 * @param str $from
	 */
	public function setMailFrom($from){
		$this->from = $from;
	}
	
	/**
	 * 设置收件人
	 * @param str $to
	 */
	public function setMailTo($to){
		$tos = explode(";", $to);
		$this->to = $tos;
	}
	
	/**
	 * 设置抄送人
	 * @param str $cc
	 */
	public function setMailCc($cc){
		$ccs = explode(";", $cc);
		$this->cc = $ccs;
	}
	
	/**
	 * 设置密送人
	 * @param str $bcc
	 */
	public function setMailBcc($bcc){
		$bccs = explode(";", $bcc);
		$this->bcc = $bccs;
	}
	
	/**
	 * 设置邮件主题
	 * @param str $bcc
	 */
	public function setSubject($subject){
		$this->subject = $subject;
	}
	
	/**
	 * 设置邮件编码
	 * @param str $charset
	 */
	public function setCharset($charset){
		$this->charset = $charset;
	}
	
	public function setBody($body){
		$this->body = $body;
	}
	
	/**
	 * 生成邮件头
	 */
	private function genHeader(){
		
		$from = $this->from;
		$to = join(";", $this->to);
		$cc = join(";", $this->cc);
		$bcc = join(";",  $this->bcc);
		$email_header =  "MIME-Version: 1.0\n";
		$email_header .= "From: {$from}\n";
		$email_header .= "To: {$to}\n";
		$email_header .= "Cc: {$cc}\n";
		$email_header .= "Bcc: {$bcc}\n";
		$email_header .= "Subject: {$this->subject}\n";
		$email_header .= "Return-Path: {$this->from}\n";
		$email_header .= "X-Priority: 3\n";
		//$email_header .= "X-Mailer: **** Mailer\n";//here ,use your hostname,like 126.com to replace ****
		$email_header .= "Content-Transfer-Encoding: 7bit\n";
		
		if($this->isatt){
			$email_header .= "Content-Type: multipart/mixed;charset={$this->charset}; boundary=\"{$this->boundary[0]}\"\n\n";
		}else{
			$email_header .= "Content-Type: " . ($this->ishtml ? "text/html" : "text/plain") . "; charset=" . $this->charset ."\"\n\n";
		}
		
		$this->header = $email_header;
		
		return $this->header;
	} 
	
	/**
	 * 处理附件
	 */
	private function dealAttachment(){
		
		$att_str = "";
		
		foreach($this->attachments as $type=>$attachment){
			if($type == 'images'){
				$att_str = "--{$this->boundary[0]}\n";
				$att_str .= "Content-Type: multipart/related; boundary=\"{$this->boundary[1]}\"\n\n";
				foreach($attachment as $img){
					$ext = "";
					switch ($img['type']){
						case 'jpg':
							$ext = "jpg";
							break;
						case 'gif':
							$ext = "gif";
							break;
						case 'png':
							$ext = "x-png";
							break;
						default:
							$ext = "gif";
							break;
					}
					$cid = $img['name'];
					$att_str .= "--{$this->boundary[1]}\n";
					$att_str .= "Content-Type: image/{$ext}; name={$img['name']}\n";
					$att_str .= "Content-ID:<{$cid}>\n";
					$att_str .= "Content-Disposition: {$img['show']}\n";
					$att_str .= "Content-Transfer-Encoding: base64\n\n";
					$att_str .= $img['data']."\n\n";
				}
				
				$att_str .= "--{$this->boundary[1]}--\n\n";
				
			}else{
				$att_str .= "--{$this->boundary[0]}\n";
				$att_str .= "Content-Type: multipart/mixed;boundary=\"{$this->boundary[1]}\"\n\n";
				foreach($attachment as $file){
					$cid = $file['name'];
					$att_str .= "--{$this->boundary[1]}\n";
					$att_str .= "Content-Type:application/octet-stream;name={$file['name']}\n";
					$att_str .= "Content-ID:<{$cid}>\n";
					$att_str .= "Content-Disposition: {$file['show']}; filename={$file['name']};\n";
					$att_str .= "Content-Transfer-Encoding: base64\n\n";
					$att_str .= $file['data']."\n\n";
				}
				
				$att_str .= "--{$this->boundary[1]}--\n\n";
			}
		}
		
		
		
		return $att_str;
	}
	
	private function genContent(){
		
		$content = "";
		$begin = $this->isatt ? "--{$this->boundary[0]}\nContent-Type: multipart/related; boundary=\"{$this->boundary[1]}\"\n\n--{$this->boundary[1]}\n":"";
		$content_type = $this->isatt ?($this->ishtml ? "Content-Type: text/html; charset=\"GB2312\"\n\n":"Content-Type: text/plain\n\n"):"";
		$end = $this->isatt ? "--{$this->boundary[1]}--\n\n":"";
		$content = $begin.$content_type.$this->body."\n".$end;
		
		return $content;
		
	}
	
	private function genEmail(){
		
		$header = $this->genHeader();
		$attachment = "";
		if($this->isatt){
			$attachment = $this->dealAttachment();
		}
		$end = $this->isatt?"--{$this->boundary[0]}--\n\n":"";
		$content = $this->genContent().$end;
		
		$mail_body = $header.$attachment.$content;
		
		return $mail_body;
	}
	
	/**
	 * 
 	 * 检查邮件信息，保证所有的邮件信息的换行都已经变成了 CRLF
	 */
	public function clearMessage($message = "")	{
		
		$message = preg_replace( "/^(\r|\n)+?(.*)$/", "\\2", $message );
	
		//-----------------------------------------
		// Bear with me...
		//-----------------------------------------
		
		$message = str_replace( "\n"          , "<br />", $message );
		$message = str_replace( "\r"          , ""      , $message );		
		$message = str_replace( "<br>" , "\r\n", $message );
		$message = str_replace( "<br />"      , "\r\n", $message );

		$message = preg_replace( "#<.+?".">#" , "" , $message );
		
		$message = str_replace( "&quot;", "\"", $message );
		$message = str_replace( "&#092;", "\\", $message );
		$message = str_replace( "&#036;", "\$", $message );
		$message = str_replace( "&#33;" , "!", $message );
		$message = str_replace( "&#39;" , "'", $message );
		$message = str_replace( "&lt;"  , "<", $message );
		$message = str_replace( "&gt;"  , ">", $message );
		$message = str_replace( "&#124;", '|', $message );
		$message = str_replace( "&amp;" , "&", $message );
		$message = str_replace( "&#58;" , ":", $message );
		$message = str_replace( "&#91;" , "[", $message );
		$message = str_replace( "&#93;" , "]", $message );
		$message = str_replace( "&#064;", '@', $message );
		$message = str_replace( "&#60;", '<', $message );
		$message = str_replace( "&#62;", '>', $message );
		$message = str_replace( "&nbsp;" , ' ' , $message );
		
		return $message;
	}
	
	/**
	 * smtpGetLine,Reads a line from the socket and returns,CODE and message from SMTP server
	 */
	public function smtpGetLine($smtp_fp){
		
		$smtp_msg = "";
		while ( false !=($line = fgets( $smtp_fp, 515 )) ){
			$smtp_msg .= $line;
				
			if ( substr($line, 3, 1) == " " ){
				break;
			}
		}
		
		return $smtp_msg;
	}
	
	/**
	 * 发送命令，失败返回false
	 */
	public function smtpSendCmd($smtp_fp,$cmd){
		
		fputs( $smtp_fp, $cmd."\r\n" );
		$smtp = array();
		$smtp_msg = $this->smtpGetLine($smtp_fp);	
		return $smtp_msg;
		
	}
	
	/**
	 * smtpCRLFEncode,换行的处理
	 */
	public function smtpCRLFEncode($data){
		
		$data .= "\n";
		$data  = str_replace( "\n", "\r\n", str_replace( "\r", "", $data ) );
		$data  = str_replace( "\n.\r\n" , "\n. \r\n", $data );
		
		return $data;
	}
	
	/**
	 * 增加一个附件
	 * @param str $filepath //文件的绝对路径
	 * @return bool
	 */
	public function addAttachment($filepath){
		if(is_file($filepath)){
			$file_name = basename($filepath);
			$file_type = pathinfo($filepath,PATHINFO_EXTENSION);
			$file_base64 = base64_encode(file_get_contents($filepath));
			$this->attachments['files'][] = array(
				'name' => $file_name,
				'data' => $file_base64,
				'type' => $file_type,
				'show' => 'attachment'
			);
		}else{
			$this->return_data['code'] = false;
			$this->return_data['msg'] = "{$filepath} is not a valid file!";
			return $this->return_data;
		}
	}
	
	/**
	 * 增加一个图片，可选择是否显示在邮件主体中
	 * @param str $imgpath //文件的绝对路径
	 * @param bool $isinline //true=>显示在邮件主体中，false=>以附件的方式呈现
	 * @return bool
	 * 
	 */
	public function addImage($imgpath,$isinline=false){
		if(is_file($imgpath)){
			$file_name = basename($imgpath);
			$file_type = pathinfo($imgpath,PATHINFO_EXTENSION);
			if(!in_array($file_type, array("png","gif","jpg","jpeg","bmp"))){
				$this->return_data['code'] = false;
				$this->return_data['msg'] = "不允许的图片格式！";
				return $this->return_data;
			}
			$file_base64 = base64_encode(file_get_contents($imgpath));
			$show = $isinline ? "inline":"attachment";
			$this->attachments['images'][] = array(
				'name' => $file_name,
				'data' => $file_base64,
				'type' => $file_type,
				'show' => $show
			);
		}else{
			$this->return_data['code'] = false;
			$this->return_data['msg'] = "{$imgpath} is not a valid file!";
			return $this->return_data;
		}
	}
	
	public function sendMail(){
		
		
		$smtpmsg  = $this->smtpGetLine($this->smtp_fp);
		$smtpcode = substr( $smtpmsg, 0, 3 );
		
		if($smtpcode == 220){
			
			//---------------------
			// HELO!, er... HELLO!
			//---------------------
			$smtp_msg  = $this->smtpSendCmd($this->smtp_fp, "HELO ".SMTP_HOSTNAME);
			$smtp_code = substr( $smtp_msg, 0, 3 );
			if ( $smtp_code != 250 ){
				$this->return_data['msg'] = "HELO";
				return $this->return_data;
			}
			//---------------------
			// AUTH LOGIN
			//---------------------
			$smtp_msg  = $this->smtpSendCmd($this->smtp_fp, "AUTH LOGIN");
			$smtp_code = substr( $smtp_msg, 0, 3 );
			if ( $smtp_code == 334 ){
				$smtp_msg = $this->smtpSendCmd($this->smtp_fp, base64_encode(SMTP_USERNAME));
				$smtp_code = substr( $smtp_msg, 0, 3 );
				if ( $smtp_code != 334  ){
					$this->return_data['msg'] = "Username not accepted from the server";
					return $this->return_data;
				}
				
				$smtp_msg = $this->smtpSendCmd($this->smtp_fp, base64_encode(SMTP_PASSWORD));
				$smtp_code = substr( $smtp_msg, 0, 3 );
				if ( $smtp_code != 235 ){
					$this->return_data['msg'] = "Password not accepted from the server";
					return $this->return_data;
				}
				
			}
			$smtp_msg = $this->smtpSendCmd($this->smtp_fp, "MAIL FROM:".$this->from);
			
			$smtp_code = substr( $smtp_msg, 0, 3 );
			if ( $smtp_code != 250 ){
				$this->return_data['msg'] = $smtp_msg;
				return $this->return_data;
			}
			$tos = $this->to;
			
			foreach ($tos as $to_mail){
				$to_mail = trim($to_mail);
				if($to_mail !== ""){
					$smtp_msg = $this->smtpSendCmd($this->smtp_fp, "RCPT TO:<".$to_mail.">");
					$smtp_code = substr( $smtp_msg, 0, 3 );
		
					if ( $smtp_code != 250 ){
						$this->return_data['msg'] = "Incorrect to email address: $to_mail".var_dump($tos);
						$this->return_data['status']['to']['undolist'] .= "{$to_mail};";
					}
					$this->return_data['status']['to']['dolist'] .= "{$to_mail};";
				}	
			}
			
			$ccs = $this->cc;
			
			foreach ($ccs as $cc_mail){
				$cc_mail = trim($cc_mail);
				if($cc_mail !== ""){
					$smtp_msg = $this->smtpSendCmd($this->smtp_fp, "RCPT TO:<".$cc_mail.">");
					$smtp_code = substr( $smtp_msg, 0, 3 );
		
					if ( $smtp_code != 250 ){
						$this->return_data['msg'] = "Incorrect cc email address: $cc_mail".var_dump($ccs);
						$this->return_data['status']['cc']['undolist'] .= "{$to_mail};";
					}
					$this->return_data['status']['cc']['dolist'] .= "{$cc_mail};";
				}	
				
			}
			
			
			//---------------------
			// SEND MAIL!
			//---------------------
			$smtp_msg = $this->smtpSendCmd($this->smtp_fp, "DATA");
			$smtp_code = substr( $smtp_msg, 0, 3 );
			if ( $smtp_code == 354 ){
				
				$mail_body = $this->genEmail();
				$mail_body = $mail_body."\n";
				$mail_body = $this->smtpCRLFEncode($mail_body);
				fputs( $this->smtp_fp, $mail_body );
			}else{
				$this->return_data['msg'] = "Error on write to SMTP server";
				return $this->return_data;
			}
			
			//---------------------
			// GO ON, NAFF OFF!
			//---------------------
			$smtp_msg = $this->smtpSendCmd($this->smtp_fp,".");
			$smtp_code = substr( $smtp_msg, 0, 3 );
			if ( $smtp_code != 250 ){
				$this->return_data['msg'] = "Error on send '.'";
				return $this->return_data;
			}
			
			
			
			$smtp = $this->smtpSendCmd($this->smtp_fp,"quit");
			$smtp_code = substr( $smtp_msg, 0, 3 );
			
			if ( ($smtp_code != 250) and ($smtp_code != 221) ){
				$this->return_data['msg'] = "Error on send 'quit'";
				return $this->return_data;
			}
			
			
			
			//---------------------
			// Tubby-bye-bye!
			//---------------------
			@fclose( $this->smtp_fp );
			
			$this->return_data['code'] = true;
			return $this->return_data;			
		}else{
			
			$this->return_data['msg'] =  "Error on smtp code ne 220";
			return $this->return_data;
		}	
	}
}
?>