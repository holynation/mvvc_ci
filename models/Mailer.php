<?php
namespace App\Models;

use CodeIgniter\Model;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

class Mailer extends Model
{

    private $mailer;
    private $ccMailAddress = '';

    function __construct()
    {
        helper('string');
        $senderMail = "payment@getdaabo.com";
        $senderName = 'noreply@ajo.com';

        $this->mailer = new PHPMailer(true);
        $this->mailer->SMTPDebug = 0;
        $this->mailer->isSMTP();

        // $this->mailer->Host = 'getdaabo.com';
        // $this->mailer->SMTPAuth = true;
        // $this->mailer->Username = $senderMail;
        // $this->mailer->Password = env('mailKey');
        // $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        // $this->mailer->Port = 465;

        $this->mailer->Host='smtp.gmail.com';
        $this->mailer->SMTPAuth=true;
        $this->mailer->Username = "holynationdevelopment@gmail.com";
        $this->mailer->Password = env('mailKeyDev');
        $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;  
        $this->mailer->Port = 587;

        $this->mailer->isHTML(true);
        $this->mailer->setFrom($senderMail, $senderName);
    }

    public function setCcMail(string $name): void
    {
        $this->ccMailAddress = $name;
    }

    public function getCcMail(): string
    {
        return $this->ccMailAddress;
    }

    private function mailerSend($recipient = null, $subject = null, $message = null)
    {
        $this->mailer->addAddress($recipient);
        if ($this->ccMailAddress != '') {
            $this->mailer->addCC($this->ccMailAddress);
        }
        $this->mailer->Subject = $subject;
        $content = $this->buildBody($message);
        $this->mailer->Body = $content;
        if ($this->mailer->send()) {
            unset($this->ccMailAddress);
            return true;
        } else {
            echo 'Mailer Error: ' . $this->mailer->ErrorInfo;
            return false;
        }
    }

    private function buildBody($content)
    {
        return $content;
    }

    public function sendAdminMail($message)
    {
        $recipient = 'info@daabo.com';
        $subject = 'Contact Message From A User';
        if (!$this->mailerSend($recipient, $subject, $message)) {
            return false;
        }
        return true;
    }

    private function mailSubject($type)
    {
        $result  = array(
            'subscription' => 'Your Device Subscription On Ajo',
            'suspension' => 'Account suspension On Ajo!',
            'plan_cancel' => 'Your Device Plan Has Been Cancelled',
            'plan_change' => 'Your Device Plan Has Been Changed',
            'plan_renew' => 'Your Device(s) Plan Has Been Renewed',
            'new_browser' => 'Unusual login attempts on your Ajo account',
            'request_claims' => 'Requesting Claims/Repairs For Device on your Ajo Account',
            'verify_account' => 'Verification of account from Ajo',
            'welcome' => 'Welcome On Board To Ajo Platform',
            'prior' => 'Reminder on your device subscription!',
            'payment_invoice' => 'Notice On Your Payment Invoice on Ajo',
            'password_reset' => 'Request to Reset your Password!',
            'password_app_token' => 'Ajo password Recovery OTP',
            'password_reset_success' => 'Ajo Password Recovery Success'
        );
        return $result[$type];
    }

    public function sendCustomerMail($recipient, $subject, $type, $customer, $info = '')
    {
        $message = $this->formatMsg($recipient, $type, $customer, $info);
        $recipient = trim($recipient);
        $subject = $this->mailSubject($subject);

        if (!$this->mailerSend($recipient, $subject, $message)) {
            return false;
        }
        return true;
    }

    private function formatMsg($recipient = '', $type = '', $customer, $info)
    {
        if ($recipient) {
            $msg = '';
            $msg .= $this->mailHeader();
            $msg .= $this->mailBody($recipient, $type, $customer, $info);
            $msg .= $this->mailFooter();
            return $msg;
        }
    }

    public function mailerTest($recipient = '', $type = '', $customer, $info)
    {
        echo $this->formatMsg($recipient, $type, $customer, $info);
    }

    private function mailHeader()
    {
        $msg = '';
        $imgLink = '';
        $msg .= '<!DOCTYPE html><html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office"><head>
	                 <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	                 <meta charset="utf-8">
	                 <meta name="viewport" content="width=device-width">
    				<meta http-equiv="X-UA-Compatible" content="IE=edge">
    				<meta name="x-apple-disable-message-reformatting">
    				<link href="https://fonts.googleapis.com/css?family=Roboto:400,600" rel="stylesheet" type="text/css">
    				<style>body,html{margin:0 auto!important;padding:0!important;height:100%!important;width:100%!important;font-family:Roboto,sans-serif!important;font-size:14px;margin-bottom:10px;line-height:24px;color:#8094ae;font-weight:400}*{-ms-text-size-adjust:100%;-webkit-text-size-adjust:100%;margin:0;padding:0}table,td{mso-table-lspace:0!important;mso-table-rspace:0!important}table{border-spacing:0!important;border-collapse:collapse!important;table-layout:fixed!important;margin:0 auto!important}table table table{table-layout:auto}a{text-decoration:none}img{-ms-interpolation-mode:bicubic}</style>
	            </head>
	        <body width="100%" style="margin: 0; padding: 0 !important; mso-line-height-rule: exactly; background-color: #f5f6fa;">';
        $msg .= '<center style="width: 100%; background-color: #f5f6fa;">';
        $msg .= '<table width="100%" border="0" cellpadding="0" cellspacing="0" bgcolor="#f5f6fa">';
        $msg .= '<tr><td style="padding: 40px 0;">';
        $msg .= '<table style="width:100%;max-width:620px;margin:0 auto;">
                        <tbody>
                            <tr>
                                <td style="text-align: center; padding-bottom:25px">
                                    <a href="ajo.com"><img style="height: 40px" src="' . $imgLink . '" alt="logo"/></a>
                                </td>
                            </tr>
                        </tbody>
                    </table>';

        $msg .= '<table style="width:100%;max-width:620px;margin:0 auto;background-color:#ffffff;color:#526484;">';
        return $msg;
    }

    private function mailBody($recipient = '', $type = '', $customer, $info)
    {
        $msg = '';
        // $receiverName = ($recipientName != null) ? $recipientName : $recipient;
        $receiverName = $recipient;
        $mailSalt = appConfig('salt');
        $email = str_replace(array('@', '.com'), array('~az~', '~09~'), $recipient);
        $temp = md5($mailSalt . $recipient);
        $expire = rndEncode(time());
        $verifyTask = rndEncode('verify');
        $accountLink = base_url("account/verify/$email/$temp/$type?task=$verifyTask&tk=$expire");
        $mailType = appConfig('type');

        switch ($mailType[$type]) {
            case 'verify_account':
                $msg = $this->loadEmailConfirmText($customer, $accountLink);
                break;
            case 'verify_success':
                $msg = $this->loadWelcomeText($customer);
                break;
            case 'forget':
                $msg = $this->loadPasswordRequestText($customer, $accountLink);
                break;
            case 'forget_success':
                $msg = $this->loadPasswordResetSuccessText($customer);
                break;
            case 'password_forget_token':
                $msg = $this->loadAppPasswordTokenText($customer, $info);
                break;

            case '2daysprior':
                $msg = $this->load2DaysPriorText($customer, $info);
                break;
            case 'subscription':
                $msg = $this->loadDeviceRegisteredText($customer, $info);
                break;
            case 'renewed':
                $msg = $this->loadDeviceRegisteredText($customer, $info, 'renew');
                break;
            case 'suspension':
                $msg = $this->loadPlanSuspensionText($customer, $info);
                break;
            case 'plan_cancel':
                $msg = $this->loadPlanCancelledText($customer, $info);
                break;
            case 'plan_change':
                $msg = $this->loadPlanChangedText($customer, $info);
                break;
            case 'new_browser':
                $msg = $this->loadUnusualActivityText($customer, $info);
                break;
            case 'request_claims':
                $msg = $this->loadRequestMadeText($customer, $info);
                break;
            case 'payment_invoice':
                $msg = $this->loadPaymentInvoiceText($customer, $info);
                break;
        }

        return $msg;
    }

    private function mailFooter()
    {
        $msg = '';
        $footerLink = "https://ajo.com";
        $updateEmailLink = "https.//ajo.com/updateEmailPreference/";
        $reserved = "&copy;Copyright 2021 Ajo. All rights reserved.";

        $msg .= '</table><table style="width:100%;max-width:620px;margin:0 auto;color:#8094ae;">
                        <tbody>
                            <tr>
                                <td style="text-align: center; padding:25px 20px 0;">
                                    <p style="font-size: 13px;">' . $reserved . '</p>
                                    
                                    <p style="padding-top: 15px; font-size: 12px;">This email was sent to you as a registered user of <a style="color: #9289ff; text-decoration:none;" href="' . $footerLink . '">ajo.com</a>.<br> To update your emails preferences <a style="color: #9289ff; text-decoration:none;" href="' . $updateEmailLink . '">click here</a>.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>';
        $msg .= '</td></tr>';
        $msg .= '</table></center></body></html>';
        return $msg;
    }

    private function loadEmailConfirmText($customerName, $confirmLink)
    {
        $msg = '';
        $msg .= '<tbody>';
        $customerName = isset($customerName['address']) ? $customerName['company_name'] : $customerName['fullname'];
        $content = '
	    		<p style="margin-bottom: 10px;">Hi ' . $customerName . ',</p>
                <p style="margin-bottom: 10px;">Welcome! <br> You are receiving this email because you have registered on our site.</p>
                <p style="margin-bottom: 10px;">Click the link below to activate your Ajo account.</p>
                <p style="margin-bottom: 25px;">This link will expire in 30 minutes and can only be used once.</p>
                <a href="' . $confirmLink . '" style="background-color:#9289ff;border-radius:4px;color:#ffffff;display:inline-block;font-size:13px;font-weight:600;line-height:44px;text-align:center;text-decoration:none;text-transform: uppercase; padding: 0 30px">Verify Email</a>
	    	';
        $content .= '
	    		<tr>
                <td style="padding: 0 30px">
                    <h4 style="font-size: 15px; color: #000000; font-weight: 600; margin: 0; text-transform: uppercase; margin-bottom: 10px">or</h4>
                    <p style="margin-bottom: 10px;">If the button above does not work, paste this link into your web browser:</p>
                    <a href="#" style="color: #9289ff; text-decoration:none;word-break: break-all;">' . $confirmLink . '</a>
                </td>
            </tr>
            <tr>
                <td style="padding: 20px 30px 40px">
                    <p>If you did not make this request, please contact us or ignore this message.</p>
                    <p style="margin: 0; font-size: 13px; line-height: 22px; color:#9ea8bb;">This is an automatically generated email, please do not reply to this email. If you face any issues, please contact us at <a style="color: #6576ff; text-decoration:none;" href="mailto:support@ajo.com"><em>support@ajo.com</em></a></p>
                </td>
            </tr>
	    	';
        $msg .= '<tr><td style="padding: 30px 30px 15px 30px;"><h2 style="font-size: 18px; color: #9289ff; font-weight: 600; margin: 0;">Confirm Your E-Mail Address</h2></td></tr>';
        $msg .= "<tr><td style='padding:0 30px 20px'>$content</td></tr>";
        $msg .= '</tbody>';
        return $msg;
    }

    private function loadWelcomeText($customerName)
    {
        $msg = '';
        $msg .= '<tbody>';
        $customerName = $customerName;
        $content = '
                    <td style="padding: 30px 30px 20px">
                        <p style="margin-bottom: 10px;">Hi ' . $customerName . ',</p>
                        <p style="margin-bottom: 10px;">We are pleased to have you registered for Ajo Device Protection.</p>
                        <p style="margin-bottom: 10px;">Your account is now verified and you can now register your devices for protection.</p>
                        <p style="margin-bottom: 15px;">We hope you will enjoy the experience. We are here for you, if you have any question, drop us a line at <a style="color: #6576ff; text-decoration:none;" href="mailto:support@ajo.com">support@ajo.com</a> anytime. </p>
                    </td>
                        ';
        $msg .= '<tr>' . $content . '</tr>';
        $msg .= '</tbody>';
        return $msg;
    }

    private function loadPasswordRequestText($customerName, $resetLink)
    {
        $msg = '';
        $msg .= '<tbody>';
        $content = '<tr>
                        <td style="text-align:center;padding: 30px 30px 15px 30px;">
                            <h2 style="font-size: 18px; color: #9289ff; font-weight: 600; margin: 0;">Reset Password</h2>
                        </td>
                    </tr>';

        $content .= '<tr>
                        <td style="text-align:center;padding: 0 30px 20px">
                            <p style="margin-bottom: 10px;">Hi ' . $customerName . ',</p>
                            <p style="margin-bottom: 18px;">Click on the link below to reset your password.</p>
                            <p style="margin-bottom: 25px;"><b style="color:red;">NOTE:</b>This link will expire in 30 minutes and can only be used once.</p>
                            <a href="' . $resetLink . '" style="background-color:#9289ff;border-radius:4px;color:#ffffff;display:inline-block;font-size:13px;font-weight:600;line-height:44px;text-align:center;text-decoration:none;text-transform: uppercase; padding: 0 25px">Reset Password</a>
                        </td>
                    </tr>
                    <tr>
		                <td style="padding: 20px 40px 40px">
		                    <p>If you did not make this request, please contact us or ignore this message.</p>
		                    <p style="margin: 0; font-size: 13px; line-height: 22px; color:#9ea8bb;">This is an automatically generated email, please do not reply to this email. If you face any issues, please contact us at <a style="color: #6576ff; text-decoration:none;" href="mailto:support@ajo.com"><em>support@ajo.com</em></a></p>
		                </td>
		            </tr>
                    ';

        $msg .= $content;
        $msg .= '</tbody>';
        return $msg;
    }

    private function loadAppPasswordTokenText($customerName, $token = '')
    {
        $msg = '';
        $msg .= '<tbody>';
        $content = '<tr>
                        <td style="text-align:center;padding: 30px 30px 15px 30px;">
                            <h2 style="font-size: 18px; color: #9289ff; font-weight: 600; margin: 0;">You Requested Password Reset</h2>
                        </td>
                    </tr>';

        $content .= '<tr>
                        <td style="text-align:center;padding: 0 30px 20px">
                            <p style="margin-bottom: 10px;">Dear ' . $customerName . ',</p>
                            <p style="margin-bottom: 25px;">Your OTP for password recovery is</p>
                            <p><b style="margin-bottom: 25px;font-size:1rem;color:#000000;">' . $token . '</b></p>
                            <p>Enter the code in the application to proceed with password recovery</p>
                            <p>The OTP will expire in one hour.</p>
                        </td>
                    </tr>';
        $content .= $this->getFooterText();
        $msg .= $content;
        $msg .= '</tbody>';
        return $msg;
    }

    private function getFooterText()
    {
        $msg = '';
        $msg .= '<tr style="text-align:center;">
		                <td style="text-align:center;padding: 20px 30px 40px">
		                    <p>If you did not make this request, please contact us or ignore this message.</p>
		                    <p style="margin: 0; font-size: 13px; line-height: 22px; color:#9ea8bb;">This is an automatically generated email, please do not reply to this email. If you face any issues, please contact us at <a style="color: #6576ff; text-decoration:none;" href="mailto:support@ajo.com"><em>support@ajo.com</em></a></p>
		                </td>
		            </tr>';
        return $msg;
    }

    private function loadPasswordResetSuccessText($customerName)
    {
        $msg = '';
        $msg .= '<tbody>';
        $content = '<tr>
                            <td style="text-align:center;padding: 30px 30px 15px 30px;">
                                <h2 style="font-size: 18px; color: #1ee0ac; font-weight: 600; margin: 0;">Your Password Has Been Reset</h2>
                            </td>
                        </tr>
                        <tr>
                            <td style="text-align:center;padding: 0 30px 20px">
                                <p style="margin-bottom: 10px;">Hi ' . $customerName . ',</p>
                                <p>You have successfully reset your password. Thank you for being with us.</p>
                            </td>
                        </tr>';
        $msg .= $content;
        $msg .= '</tbody>';
        return $msg;
    }

    private function load2DaysPriorText($customerName, $info)
    {
        $msg = '';
        $device_name = $info['device_model'];
        $msg .= '<tbody>';
        $content = '
                    <td style="padding: 30px 30px 20px">
                        <p style="margin-bottom: 10px;">Hi ' . $customerName . ',</p>
                        <p>Device Name: ' . $device_name . '</p>
                        <p style="margin-bottom: 10px;">
				            Just like Shola, an active user like you on Ajo, said in one of his testimonies he shared...... <br>
				            I always make sure I have my bank account funded, especially before the end of the month to avoid stories that touch the heart <br/>
				            We hope you are always ready too, like Shola. <br/>
				            It\'s 2 days before your subscription expires... <br />
				            Just a reminder... <br/>
				            Get your card ready for re-subscription... 
				            Keep earning and learning. If you love it, we love it too.
				            </p>
                    </td>
                        ';
        $msg .= '<tr>' . $content . '</tr>';
        $msg .= '</tbody>';
        return $msg;
    }

    private function loadDeviceRegisteredText($customerName, $info, $type = '')
    {
        $msg = '';
        $text = ($type == 'renew') ? "Your device has been renewed and is now protected" : "Your device has been registered and is now protected";
        $msg .= '<tbody>';
        $content = '<td style="text-align:center;padding: 30px 30px 20px">
                        <h5 style="margin-bottom: 24px; color: #526484; font-size: 20px; font-weight: 400; line-height: 28px;">Hi ' . $customerName . '.<br>' . $text . '</h5>
                        <p style="margin-bottom: 15px; color: #526484; font-size: 16px;"><h1>Device Information</h1><br>Device Name - ' . $info['device_model'] . '<br> Device ID - ' . $info['device_hash'] . ' <br> Plan - ' . $info['plan'] . ' <br> Price - ₦' . $info['amount'] . '.</p><br/>
                        <p style="margin-bottom: 15px;">We hope you will enjoy the experience. We are here for you, if you have any question, drop us a line at <a style="color: #6576ff; text-decoration:none;" href="mailto:support@ajo.com">support@ajo.com</a> anytime. </p>
                    </td>';

        $msg .= '<tr>' . $content . '</tr>';
        $msg .= '</tbody>';
        return $msg;
    }

    private function loadPlanCancelledText($customerName, $info)
    {
        $msg = '';
        $msg .= '<tbody>';
        $content = '<tr>
                        <td style="text-align:center;padding: 30px 30px 20px">
                            <h5 style="margin-bottom: 24px; color: #526484; font-size: 20px; font-weight: 400; line-height: 28px;">Hi ' . $customerName . '<br>Your device plan has been cancelled</h5>
                            <p style="margin-bottom: 15px; color: #526484; font-size: 16px;"><h1>Device Information</h1><br>Device Name - ' . $info['device_model'] . '<br> Device ID - ' . $info['device_hash'] . ' <br> Plan - ' . $info['plan'] . ' <br> Price - ₦' . $info['amount'] . '.</p><br/>
                            <p style="margin-bottom: 15px;">We hope you will enjoy the experience. We are here for you, if you have any question, drop us a line at <a style="color: #6576ff; text-decoration:none;" href="mailto:support@getdaabo.com">support@getdaabo.com</a> anytime. </p>
                        </td>
                    </tr>';

        $msg .= '<tr>' . $content . '</tr>';
        $msg .= '</tbody>';
        return $msg;
    }

    private function loadPlanChangedText($customerName, $info)
    {
        $msg = '';
        $msg .= '<tbody>';
        $content = '<tr>
                        <td style="text-align:center;padding: 30px 30px 20px">
                            <h5 style="margin-bottom: 24px; color: #526484; font-size: 20px; font-weight: 400; line-height: 28px;">Hi ' . $customerName . '<br />Your device plan has been changed</h5>
                            <p style="margin-bottom: 15px; color: #526484; font-size: 16px;"><h1>Device Information</h1><br>Device Name - ' . $info['device_model'] . '<br> Device ID - ' . $info['device_hash'] . ' <br> New Plan - ' . $info['plan'] . ' <br /> New Price - ₦' . $info['amount'] . '.</p><br/>
                            <p style="margin-bottom: 15px;">We hope you will enjoy the experience. We are here for you, if you have any question, drop us a line at <a style="color: #6576ff; text-decoration:none;" href="mailto:support@getdaabo.com">support@getdaabo.com</a> anytime. </p>
                        </td>
                    </tr>';

        $msg .= '<tr>' . $content . '</tr>';
        $msg .= '</tbody>';
        return $msg;
    }

    private function loadUnusualActivityText($customerName, $info)
    {
        $msg = '';
        $msg .= '<tbody>';
        $loginDate = date('Y-m-d H:i:s');
        $content = '<tr>
                            <td style="text-align:center;padding: 30px 30px 15px 30px;">
                                <h2 style="font-size: 18px; color: #D93025; font-weight: 600; margin: 0;">New Device Login Activity</h2>
                            </td>
                        </tr>
                        <tr>
                            <td style="text-align:center;padding: 0 30px 20px">
                                <p style="margin-bottom: 10px;">Hi ' . $customerName . ',</p>
                                <p>We noticed you logged in to Daabo account from a new device on: ' . dateFormatDevice($loginDate) . ' at ' . localTimeRead($loginDate) . '.</p>
                                <p>&nbsp;</p>
                                <p>Device Info: ' . $info['browser'] . '<br />IP address: ' . $info['ip'] . '</p>
                                <p>&nbsp;</p>
                                <p>If you did not initiate this log in, please change your password.</p>
                            </td>
                        </tr>';
        $msg .= $content;
        $msg .= '</tbody>';
        return $msg;
    }

    private function loadPaymentInvoiceText($customerName, $info)
    {
        $msg = '';
        $msg .= '<tbody>';
        $content = '<td style="text-align:center;padding: 30px 30px 20px">
                        <h5 style="margin-bottom: 24px; color: #526484; font-size: 20px; font-weight: 400; line-height: 28px;">Hi .' . $customerName . '<br>We are pleased to inform you that your payment of ₦' . $info['amount'] . ' was successful. Your order ID is ' . $info['receipt_ref'] . '</h5>
                        <p style="margin-bottom: 15px; color: #526484; font-size: 16px;"><h1>Device Information</h1><br>Device Name - ' . $info['device_model'] . '<br> Device ID - ' . $info['device_hash'] . ' <br> Plan - ' . $info['plan'] . ' <br> Price - ₦' . $info['amount'] . '.</p><br/>
                        <p style="margin-bottom: 15px;">We hope you will enjoy the experience. We are here for you, if you have any question, drop us a line at <a style="color: #6576ff; text-decoration:none;" href="mailto:support@getdaabo.com">support@getdaabo.com</a> anytime. </p>
                    </td>';
        $msg .= '<tr>' . $content . '</tr>';
        $msg .= '</tbody>';
        return $msg;
    }

    private function loadPlanSuspensionText($customerName, $info)
    {
        $msg = '';
        $msg .= '<tbody>';
        $content = '<tr>
                        <td style="text-align:center;padding: 30px 30px 20px">
                            <h5 style="margin-bottom: 24px; color: #526484; font-size: 20px; font-weight: 400; line-height: 28px;">Hi ' . $customerName . '<br>Your device plan had been suspended</h5>
                            <p>We noticed your subscription could not be initiated due to an expired card... <br>
                    Kindly replace your card with an active card so that you do not miss out from the goodies we intend to give out to our active subscribers soon.</p>
                            <p style="margin-bottom: 15px; color: #526484; font-size: 16px;"><h1>Device Information</h1><br>Device Name - ' . $info['device_model'] . '<br> Device ID - ' . $info['device_hash'] . ' <br> Plan - ' . $info['plan'] . ' <br> Price - ₦' . $info['amount'] . '.</p><br/>
                            <p style="margin-bottom: 15px;">We hope you will enjoy the experience. We are here for you, if you have any question, drop us a line at <a style="color: #6576ff; text-decoration:none;" href="mailto:support@getdaabo.com">support@getdaabo.com</a> anytime. </p>
                        </td>
                    </tr>';

        $msg .= '<tr>' . $content . '</tr>';
        $msg .= '</tbody>';
        return $msg;
    }

    private function loadRequestApprovedText($customerName, $info)
    {
        $msg = '';
        $customerName = "";
        $device = "";
        $msg .= '<tbody>';
        $content = '<td style="text-align: center; padding: 30px 30px 20px">
                        <div>
                            <h5 style="margin-bottom: 24px; color: #526484; font-size: 20px; font-weight: 400; line-height: 28px;">Hi ' . $customerName . '<br>Your claim/repair request with the details below has been approved, we will send you an email with directions on how we would provide the service to you.</h5>
                            
                            <div class="">
                                <h4 class="title">Repair</h4>
                            </div>
                            <div class="margin-bottom: 15px; color: #526484; font-size: 16px;">
                                <div class="nk-block-between flex-wrap g-3">
                                    <span class="sub-text">Device Name : Samsung SJ40 </span>
                                    <br>
                                    <span class="sub-text mt-n1">Date and time of request : 15 Oct, 2019 09:45 PM</span>
                                </div>
                                <div class="nk-modal-head mt-sm-5 mt-4 mb-4">
                                    <h5 class="title">Claim/Repair Info</h5>
                                </div>
                                <div class="row gy-3">
                                    <div class="col-lg-6">
                                        <span class="sub-text">Order ID :</span>
                                        <span class="caption-text">YWLX52JG73</span>
                                    </div>
                                    <div class="col-lg-6">
                                        <span class="sub-text">Device ID :</span>
                                        <span class="caption-text text-break">23456789</span>
                                    </div>
                                    <div class="col-lg-6">
                                        <span class="sub-text">Reason :</span>
                                        <span class="caption-text">Screen Damage</span>
                                    </div>
                                    <div class="col-lg-6">
                                        <span class="sub-text">Plan :</span>
                                        <span class="caption-text">Montly</span>
                                    </div>
                                </div>
                                <div class="nk-modal-head mt-sm-5 mt-4 mb-4">
                                    <h5 class="title">Claim/Repair Details</h5>
                                </div>
                                <div class="row gy-3">
                                    <div class="col-lg-6">
                                        <span class="sub-text">Date of incident :</span>
                                        <span class="caption-text">12/09/2021</span>
                                    </div>
                                    <div class="col-lg-6">
                                        <span class="sub-text">Has this occured before to this device? :</span>
                                        <span class="caption-text align-center">No</span>
                                    </div>
                                    <div class="col-lg-6">
                                        <span class="sub-text">Has this device been fixed before :</span>
                                        <span class="caption-text text-break">Yes</span>
                                    </div>
                                    <div class="col-lg-6">
                                        <span class="sub-text">Where did the incident happen :</span>
                                        <span class="caption-text text-break">Alabama USA</span>
                                    </div>
                                    <div class="col-lg-12">
                                        <span class="sub-text">Your current location :</span>
                                        <span class="caption-text text-break">Ikeja Lagos</span>
                                    </div>
                                    <div class="col-lg-12">
                                        <span class="sub-text">Details :</span>
                                        <span class="caption-text">I was going some where and it fell on the floor</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <p style="margin: 0; font-size: 13px; line-height: 22px; color:#9ea8bb;">This is an automatically generated email, please do not reply to this email. We are here, if you have any questions, drop us a line at <a style="color: #6576ff; text-decoration:none;" href="mailto:support@getdaabo.com"><em>support@getdaabo.com</em></a></p>
                        </td>';
        $msg .= '<tr>' . $content . '</tr>';
        $msg .= '</tbody>';
        return $msg;
    }

    private function loadRequestMadeText($customerName, $info)
    {
        $msg = '';
        $msg .= '<tbody>';
        $content = '<td style="text-align: center; padding: 30px 30px 20px">
                        <div>
                            <h5 style="margin-bottom: 24px; color: #526484; font-size: 20px; font-weight: 400; line-height: 28px;">Hi ' . $customerName . '<br>You have made a claim/repair request</h5>
                            
                            <div class="">
                                <h4 class="title">Repair</h4>
                            </div>
                            <div class="margin-bottom: 15px; color: #526484; font-size: 16px;">
                                <div class="nk-block-between flex-wrap g-3">
                                    <span class="sub-text">Device Name: ' . $info['device_model'] . ' </span>
                                    <br>
                                    <span class="sub-text mt-n1">Date and time of request : ' . dateFormatDevice(date('Y-m-d')) . ' ' . localTimeRead(date('H:i:s')) . '</span>
                                </div>
                                <div class="nk-modal-head mt-sm-5 mt-4 mb-4">
                                    <h5 class="title">Claim/Repair Info</h5>
                                </div>
                                <div class="row gy-3">
                                    <div class="col-lg-6">
                                        <span class="sub-text">Order ID:</span>
                                        <span class="caption-text">' . $info['order_id'] . '</span>
                                    </div>
                                    <div class="col-lg-6">
                                        <span class="sub-text">Device ID:</span>
                                        <span class="caption-text text-break">' . $info['device_hash'] . '</span>
                                    </div>
                                    <div class="col-lg-6">
                                        <span class="sub-text">Reason:</span>
                                        <span class="caption-text">' . $info['incident'] . '</span>
                                    </div>
                                    <div class="col-lg-6">
                                        <span class="sub-text">Plan:</span>
                                        <span class="caption-text">' . $info['plan'] . '</span>
                                    </div>
                                </div>
                                <p>&nbsp;</p>
                                <div class="nk-modal-head mt-sm-5 mt-4 mb-4">
                                    <h5 class="title">Claim/Repair Details</h5>
                                </div>
                                <div class="row gy-3">
                                    <div class="col-lg-6">
                                        <span class="sub-text">Date of incident:</span>
                                        <span class="caption-text">' . $info['date_of_incident'] . '</span>
                                    </div>
                                    <div class="col-lg-6">
                                        <span class="sub-text">Has this occured before to this device?</span>
                                        <span class="caption-text align-center">' . ($info['has_occur'] ? 'Yes' : 'No') . '</span>
                                    </div>
                                    <div class="col-lg-6">
                                        <span class="sub-text">Has this device been fixed before?</span>
                                        <span class="caption-text text-break">' . ($info['has_device_fixed'] ? 'Yes' : 'No') . '</span>
                                    </div>
                                    <div class="col-lg-6">
                                        <span class="sub-text">Where did the incident happen?</span>
                                        <span class="caption-text text-break">' . $info['incident_location'] . '</span>
                                    </div>
                                    <div class="col-lg-12">
                                        <span class="sub-text">Your current location:</span>
                                        <span class="caption-text text-break">' . $info['current_location'] . '</span>
                                    </div>
                                    <div class="col-lg-12">
                                        <span class="sub-text">Details:</span>
                                        <span class="caption-text">' . $info['details'] . '</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <p>&nbsp;</p>
                        <p style="margin: 0; font-size: 13px; line-height: 22px; color:#9ea8bb;">This is an automatically generated email, please do not reply to this email. We are here, if you have any questions, drop us a line at <a style="color: #6576ff; text-decoration:none;" href="mailto:support@getdaabo.com"><em>support@getdaabo.com</em></a></p>
                        </td>';
        $msg .= '<tr>' . $content . '</tr>';
        $msg .= '</tbody>';
        return $msg;
    }

    private function loadRequestMoreInfoText($customerName, $info)
    {
        $msg = '';
        $customerName = "";
        $device = "";
        $msg .= '<tbody>';
        $content = '<td style="text-align: center; padding: 30px 30px 20px">
                        <div>
                            <h5 style="margin-bottom: 24px; color: #526484; font-size: 20px; font-weight: 400; line-height: 28px;">Hi ' . $customerName . '<br>Your claim/repair request with the details below is still pending, we will need you to send more information on what happened for us to approve your request.</h5>
                            
                            <div class="">
                                <h4 class="title">Repair</h4>
                            </div>
                            <div class="margin-bottom: 15px; color: #526484; font-size: 16px;">
                                <div class="nk-block-between flex-wrap g-3">
                                    <span class="sub-text">Device Name : Samsung SJ40 </span>
                                    <br>
                                    <span class="sub-text mt-n1">Date and time of request: 15 Oct, 2019 09:45 PM</span>
                                </div>
                                <div class="nk-modal-head mt-sm-5 mt-4 mb-4">
                                    <h5 class="title">Claim/Repair Info</h5>
                                </div>
                                <div class="row gy-3">
                                    <div class="col-lg-6">
                                        <span class="sub-text">Order ID:</span>
                                        <span class="caption-text">YWLX52JG73</span>
                                    </div>
                                    <div class="col-lg-6">
                                        <span class="sub-text">Device ID:</span>
                                        <span class="caption-text text-break">23456789</span>
                                    </div>
                                    <div class="col-lg-6">
                                        <span class="sub-text">Reason:</span>
                                        <span class="caption-text">Screen Damage</span>
                                    </div>
                                    <div class="col-lg-6">
                                        <span class="sub-text">Plan:</span>
                                        <span class="caption-text">Montly</span>
                                    </div>
                                </div>
                                <div class="nk-modal-head mt-sm-5 mt-4 mb-4">
                                    <h5 class="title">Claim/Repair Details</h5>
                                </div>
                                <div class="row gy-3">
                                    <div class="col-lg-6">
                                        <span class="sub-text">Date of incident:</span>
                                        <span class="caption-text">12/09/2021</span>
                                    </div>
                                    <div class="col-lg-6">
                                        <span class="sub-text">Has this occured before to this device?</span>
                                        <span class="caption-text align-center">No</span>
                                    </div>
                                    <div class="col-lg-6">
                                        <span class="sub-text">Has this device been fixed before?</span>
                                        <span class="caption-text text-break">Yes</span>
                                    </div>
                                    <div class="col-lg-6">
                                        <span class="sub-text">Where did the incident happen?</span>
                                        <span class="caption-text text-break">Alabama USA</span>
                                    </div>
                                    <div class="col-lg-12">
                                        <span class="sub-text">Your current location:</span>
                                        <span class="caption-text text-break">Ikeja Lagos</span>
                                    </div>
                                    <div class="col-lg-12">
                                        <span class="sub-text">Details:</span>
                                        <span class="caption-text">I was going some where and it fell on the floor</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <p style="margin: 0; font-size: 13px; line-height: 22px; color:#9ea8bb;">This is an automatically generated email, please do not reply to this email. We are here, if you have any questions, drop us a line at <a style="color: #6576ff; text-decoration:none;" href="mailto:support@getdaabo.com"><em>support@getdaabo.com</em></a></p>
                        </td>';
        $msg .= '<tr>' . $content . '</tr>';
        $msg .= '</tbody>';
        return $msg;
    }

    private function loadRequestRejectedText($customerName, $info)
    {
        $msg = '';
        $customerName = "";
        $device = "";
        $msg .= '<tbody>';
        $content = '<td style="text-align: center; padding: 30px 30px 20px">
                        <div>
                            <h5 style="margin-bottom: 24px; color: #526484; font-size: 20px; font-weight: 400; line-height: 28px;">Hi ' . $customerName . '<br>Your claim/repair request with the details below has been rejected because we could not validate the infrmation you provided. Please contact us for more information and how we can help.</h5>
                            
                            <div class="">
                                <h4 class="title">Repair</h4>
                            </div>
                            <div class="margin-bottom: 15px; color: #526484; font-size: 16px;">
                                <div class="nk-block-between flex-wrap g-3">
                                    <span class="sub-text">Device Name: Samsung SJ40 </span>
                                    <br>
                                    <span class="sub-text mt-n1">Date and time of request : 15 Oct, 2019 09:45 PM</span>
                                </div>
                                <div class="nk-modal-head mt-sm-5 mt-4 mb-4">
                                    <h5 class="title">Claim/Repair Info</h5>
                                </div>
                                <div class="row gy-3">
                                    <div class="col-lg-6">
                                        <span class="sub-text">Order ID:</span>
                                        <span class="caption-text">YWLX52JG73</span>
                                    </div>
                                    <div class="col-lg-6">
                                        <span class="sub-text">Device ID:</span>
                                        <span class="caption-text text-break">23456789</span>
                                    </div>
                                    <div class="col-lg-6">
                                        <span class="sub-text">Reason:</span>
                                        <span class="caption-text">Screen Damage</span>
                                    </div>
                                    <div class="col-lg-6">
                                        <span class="sub-text">Plan:</span>
                                        <span class="caption-text">Montly</span>
                                    </div>
                                </div>
                                <div class="nk-modal-head mt-sm-5 mt-4 mb-4">
                                    <h5 class="title">Claim/Repair Details</h5>
                                </div>
                                <div class="row gy-3">
                                    <div class="col-lg-6">
                                        <span class="sub-text">Date of incident:</span>
                                        <span class="caption-text">12/09/2021</span>
                                    </div>
                                    <div class="col-lg-6">
                                        <span class="sub-text">Has this occured before to this device?</span>
                                        <span class="caption-text align-center">No</span>
                                    </div>
                                    <div class="col-lg-6">
                                        <span class="sub-text">Has this device been fixed before?</span>
                                        <span class="caption-text text-break">Yes</span>
                                    </div>
                                    <div class="col-lg-6">
                                        <span class="sub-text">Where did the incident happen?</span>
                                        <span class="caption-text text-break">Alabama USA</span>
                                    </div>
                                    <div class="col-lg-12">
                                        <span class="sub-text">Your current location:</span>
                                        <span class="caption-text text-break">Ikeja Lagos</span>
                                    </div>
                                    <div class="col-lg-12">
                                        <span class="sub-text">Details:</span>
                                        <span class="caption-text">I was going some where and it fell on the floor</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <p style="margin: 0; font-size: 13px; line-height: 22px; color:#9ea8bb;">This is an automatically generated email, please do not reply to this email. We are here, if you have any questions, drop us a line at <a style="color: #6576ff; text-decoration:none;" href="mailto:support@getdaabo.com"><em>support@getdaabo.com</em></a></p>
                        </td>';
        $msg .= '<tr>' . $content . '</tr>';
        $msg .= '</tbody>';
        return $msg;
    }

    private function loadText()
    {
        $msg = '';
        $msg .= '<tbody>';
        $msgFoot = '<tr>
		                <td style="padding: 20px 30px 40px">
		                    <p>If you did not make this request, please contact us or ignore this message.</p>
		                    <p style="margin: 0; font-size: 13px; line-height: 22px; color:#9ea8bb;">This is an automatically generated email, please do not reply to this email. If you face any issues, please contact us at <a style="color: #6576ff; text-decoration:none;" href="mailto:support@getdaabo.com"><em>support@getdaabo.com</em></a></p>
		                </td>
		            </tr>';
        $content = '';

        $msg .= '<tr>' . $content . '</tr>';
        $msg .= '</tbody>';
        $msg .= '';
        return $msg;
    }
}
