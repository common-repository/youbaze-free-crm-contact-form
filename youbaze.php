<?php
	/*
	Plugin Name: YouBaze Free CRM Contact Form Plugin
	Description: Insert and customize contact forms and manage enquiries on your Wordpress website and import them directly into your Free YouBaze CRM account!
	Version: 1.0.0
	Author: YouBaze
	Author URI: http://www.youbaze.com
	*/
	
	register_activation_hook(__FILE__, 'youbaze_activate');
	add_action('admin_menu', 'youbaze_menu');
		
	function youbaze_getOptionName()
	{
		return 'youbaze';
	}
	
	function youbaze_activate()
	{
		$optionexists = false;
		$vals = array();
		$currentoption = get_option(youbaze_getOptionName());
		if ($currentoption!==false)
		{
			$vals = $currentoption;
			$optionexists = true;
		}
		
		$defaultvals = array('youbaze_email' => 'your.youbaze.email@example.com');
		foreach ($defaultvals as $k => $defaultval)
		{
			if (!isset($vals[$k]))
				$vals[$k] = $defaultval;
		}
		
		if ($optionexists)
			update_option(youbaze_getOptionName(), $vals);
		else
			add_option(youbaze_getOptionName(), $vals);
	}
	
	function youbaze_menu() 
	{
		add_options_page('YouBaze', 'YouBaze', 'manage_options', 'youbaze', 'youbaze_options');
	}

	function youbaze_options() 
	{
		if (!current_user_can('manage_options'))
			wp_die('You do not have the neccessary permissions to access this page.');
		
		$saved = false;
		$error = false;
		$fields = array('youbaze_email');
		$optionvalue = get_option(youbaze_getOptionName());
		
		foreach ($fields as $field)
		{
			$$field = isset($_POST[$field]) ? trim($_POST[$field]) : $optionvalue[$field];
		}

		if (isset($_POST['Submit']))
		{
			if (!youbaze_validateEmail($youbaze_email))
				$error = 'The email address you entered is invalid.';

			if ($error===false)
			{
				$vals = array();
				foreach ($optionvalue as $key => $value)
					$vals[$key] = $$key;

				update_option(youbaze_getOptionName(), $vals);
				$optionvalue = $vals;
				$saved = true;
			}
		}
		?>
		<div class="wrap">
			<h2>YouBaze</h2>
			<?php
				if ($saved)
				{
					?>
					<div class="updated"><p><strong>Your settings have been saved</strong></p></div>
					<?php
				} elseif ($error!==false)
				{
					?>
					<div class="error"><p><strong><?php echo youbaze_html($error);?></strong></p></div>
					<?php
				}
			?>
			
			<form id="youbazeform" name="form1" method="post" action="" enctype="multipart/form-data">
				<h3>General settings</h3>
				<table class="form-table">
					<tbody>
						<tr>
							<td><label for="youbaze_email">YouBaze email address</label></td>
							<td><input type="text" name="youbaze_email" id="youbaze_email" value="<?php echo youbaze_html($youbaze_email);?>" /></td>
						</tr>
					</tbody>
				</table>
				<p>Set up your contact form in a widget and add this widget to the page you like. This way you can create contact forms for different purposes: question about your products/services, request for a meeting, sample request, customer complaint, RMA form, etcetera.</p>
				<p class="submit">
					<input type="submit" name="Submit" class="button-primary" value="Opslaan" />
				</p>
			</form>
		</div>
		<?php
	}
	
	function youbaze_validateEmail($email)
	{
		return preg_match('/^(?!(?>(?1)"?(?>\\\[ -~]|[^"])"?(?1)){255,})(?!(?>(?1)"?(?>\\\[ -~]|[^"])"?(?1)){65,}@)((?>(?>(?>((?>(?>(?>\x0D\x0A)?[\t ])+|(?>[\t ]*\x0D\x0A)?[\t ]+)?)(\((?>(?2)(?>[\x01-\x08\x0B\x0C\x0E-\'*-\[\]-\x7F]|\\\[\x00-\x7F]|(?3)))*(?2)\)))+(?2))|(?2))?)([!#-\'*+\/-9=?^-~-]+|"(?>(?2)(?>[\x01-\x08\x0B\x0C\x0E-!#-\[\]-\x7F]|\\\[\x00-\x7F]))*(?2)")(?>(?1)\.(?1)(?4))*(?1)@(?!(?1)[a-z\d-]{64,})(?1)(?>([a-z\d](?>[a-z\d-]*[a-z\d])?)(?>(?1)\.(?!(?1)[a-z\d-]{64,})(?1)(?5)){0,126}|\[(?:(?>IPv6:(?>([a-f\d]{1,4})(?>:(?6)){7}|(?!(?:.*[a-f\d][:\]]){8,})((?6)(?>:(?6)){0,6})?::(?7)?))|(?>(?>IPv6:(?>(?6)(?>:(?6)){5}:|(?!(?:.*[a-f\d]:){6,})(?8)?::(?>((?6)(?>:(?6)){0,4}):)?))?(25[0-5]|2[0-4]\d|1\d{2}|[1-9]?\d)(?>\.(?9)){3}))\])(?1)$/isD', $email)===1;
	}
	
	function youbaze_html($string)
    {
        return htmlentities($string, ENT_COMPAT, "utf-8");
    }
	
	function youbaze_admin_css()
	{
		wp_enqueue_style('youbaze-admin-css', plugins_url('css/youbaze_admin.css', __FILE__), false, '1.0.0', 'all');
	}
	add_action('admin_enqueue_scripts', 'youbaze_admin_css');
	
	function youbaze_css()
	{
        wp_enqueue_style('youbaze-css', plugins_url('css/youbaze.css', __FILE__), false, '1.0.0', 'all');
    }
    add_action('wp_enqueue_scripts', "youbaze_css");
	
	class YouBazeWidget extends WP_Widget
	{
		private $jsonString;
		private $attachment;
		
		function __construct() 
		{
			$this->jsonString = '';
			$this->attachment = false;
			parent::__construct('youbaze', __('YouBaze', 'text_domain'), array('description' => __('YouBaze Contact Form', 'text_domain')));
		}

		private function getYouBazeSettings()
		{
			return array(
				'contact_type_id' => 'Customer consumer',
				'ordersource_id' => 'Other',
				'terms_of_payment' => 8,
				'salutation' => 1
			);
		}
		
		private function getFormSettings()
		{
			return array(
				'cssclass' => '',
				'heading' => 'Contact us',
				'submit' => 'Send',
				'error_incomplete' => 'You did not complete all the required fields.',
				'error_invalidemail' => 'The entered email address is invalid.',
				'success' => 'Your message has beent sent!'
			);
		}
		
		private function getEmailSettings()
		{
			return array(
				'sender_name' => 'Your name',
				'sender_email' => 'your.domain.email@example.com',
				'subject' => 'Website enquiry',
				'confirmation_email' => "Dear Sir/Madam,\r\n\r\nThank you for your enquiry. We will contact you shortly.\r\n\r\nBest regards\r\n\r\nYour name"
			);
		}
		
		private function getFormFields()
		{
			return array(
				'gender_text' => 'Gender', 'gender_enabled' => 1, 'gender_mandatory' => 1,
				'gender_male_text' => 'Male',
				'gender_female_text' => 'Female',
				'firstname_text' => 'First name', 'firstname_enabled' => 0, 'firstname_mandatory' => 0,
				'initials_text' => 'Initials', 'initials_enabled' => 1, 'initials_mandatory' => 1,
				'insertion_text' => 'Insertion', 'insertion_enabled' => 1, 'insertion_mandatory' => 0,
				'lastname_text' => 'Last name', 'lastname_enabled' => 1, 'lastname_mandatory' => 1,
				'function_text' => 'Position', 'function_enabled' => 0, 'function_mandatory' => 0,
				'street_text' => 'Street', 'street_enabled' => 0, 'street_mandatory' => 0,
				'house_number_text' => 'House number', 'house_number_enabled' => 0, 'house_number_mandatory' => 0,
				'house_number_suffix_text' => 'Suffix', 'house_number_suffix_enabled' => 0, 'house_number_suffix_mandatory' => 0,
				'address2_text' => 'Address 2', 'address2_enabled' => 0, 'address2_mandatory' => 0,
				'zipcode_text' => 'Zipcode', 'zipcode_enabled' => 0, 'zipcode_mandatory' => 0,
				'city_text' => 'City', 'city_enabled' => 0, 'city_mandatory' => 0,
				'state_text' => 'State', 'state_enabled' => 0, 'state_mandatory' => 0, 
				'pobox_text' => 'P.O. Box', 'pobox_enabled' => 0, 'pobox_mandatory' => 0,
				'pobox_zipcode_text' => 'P.O. Box Zipcode', 'pobox_zipcode_enabled' => 0, 'pobox_zipcode_mandatory' => 0,
				'pobox_city_text' => 'P.O. Box City', 'pobox_city_enabled' => 0, 'pobox_city_mandatory' => 0,
				'company_text' => 'Company', 'company_enabled' => 0, 'company_mandatory' => 0,
				'department_text' => 'Department', 'department_enabled' => 0, 'department_mandatory' => 0,
				'cocnumber_text' => 'Chamber of commerce', 'cocnumber_enabled' => 0, 'cocnumber_mandatory' => 0,
				'vatnumber_text' => 'VAT number', 'vatnumber_enabled' => 0, 'vatnumber_mandatory' => 0,
				'email_text' => 'Email', 'email_enabled' => 1, 'email_mandatory' => 1,
				'email_general_text' => 'Email general', 'email_general_enabled' => 0, 'email_general_mandatory' => 0,
				'phone_text' => 'Phone', 'phone_enabled' => 0, 'phone_mandatory' => 0,
				'phone_direct_text' => 'Phone direct', 'phone_direct_enabled' => 0, 'phone_direct_mandatory' => 0,
				'mobile_text' => 'Mobile', 'mobile_enabled' => 0, 'mobile_mandatory' => 0,
				'skype_text' => 'Skype', 'skype_enabled' => 0, 'skype_mandatory' => 0,
				'website_text' => 'Website', 'website_enabled' => 0, 'website_mandatory' => 0,
				'twitter_text' => 'Twitter', 'twitter_enabled' => 0, 'twitter_mandatory' => 0,
				'facebook_text' => 'Facebook', 'facebook_enabled' => 0, 'facebook_mandatory' => 0,
				'instagram_text' => 'Instagram', 'instagram_enabled' => 0, 'instagram_mandatory' => 0,
				'googleplus_text' => 'Google+', 'googleplus_enabled' => 0, 'googleplus_mandatory' => 0,
				'tumblr_text' => 'Tumblr', 'tumblr_enabled' => 0, 'tumblr_mandatory' => 0,
				'linkedin_text' => 'Linkedin', 'linkedin_enabled' => 0, 'linkedin_mandatory' => 0, 			
				'blog_text' => 'Blog', 'blog_enabled' => 0, 'blog_mandatory' => 0,
				'subject_text' => 'Subject', 'subject_enabled' => 1, 'subject_mandatory' => 1,
				'text_text' => 'Text', 'text_enabled' => 1, 'text_mandatory' => 1,
				'text_text_default' => '',
				'attachment_text' => 'Attachment', 'attachment_enabled' => 0, 'attachment_mandatory' => 0
			);
		}
		
		private function getFieldLabel($field)
		{
			$labels = array(
				'contact_type_id' => 'Contact type',
				'ordersource_id' => 'Media source',
				'terms_of_payment' => 'Terms of payment',
				'salutation' => 'Salutation',
				
				'subject' => 'Email subject',
				'sender_name' => 'Sender name',
				'sender_email' => 'Sender email',
				'confirmation_email' => 'Confirmation email text',
				
				'cssclass' => 'Extra CSS class(es)',
				'heading' => 'Form heading',
				'submit' => 'Submit button text',
				'error_incomplete' => 'Form incomplete error',
				'error_invalidemail' => 'Invalid email address error',
				'success' => 'Success/completion message',
				
				'gender_text' => 'Gender',
				'gender_male_text' => 'Gender male',
				'gender_female_text' => 'Gender female',
				'firstname_text' => 'First name',
				'initials_text' => 'Initials',
				'insertion_text' => 'Insertion',
				'lastname_text' => 'Last name',
				'function_text' => 'Position',
				'zipcode_text' => 'Zipcode',
				'house_number_text' => 'House number',
				'house_number_suffix_text' => 'Suffix',
				'street_text' => 'Street',
				'address2_text' => 'Address 2',
				'city_text' => 'City',
				'state_text' => 'State',
				'pobox_text' => 'P.O. Box',
				'pobox_zipcode_text' => 'P.O. Box Zipcode',
				'pobox_city_text' => 'P.O. Box City',
				'company_text' => 'Company', 
				'department_text' => 'Department',
				'cocnumber_text' => 'Chamber of commerce', 
				'vatnumber_text' => 'VAT number',
				'email_text' => 'Email',
				'email_general_text' => 'Email general',
				'phone_text' => 'Phone',
				'phone_direct_text' => 'Phone direct',
				'mobile_text' => 'Mobile', 
				'skype_text' => 'Skype',
				'website_text' => 'Website',
				'twitter_text' => 'Twitter', 
				'facebook_text' => 'Facebook',
				'instagram_text' => 'Instagram',
				'googleplus_text' => 'Google+',
				'tumblr_text' => 'Tumblr',
				'linkedin_text' => 'Linkedin',
				'blog_text' => 'Blog',
				'subject_text' => 'Subject',
				'text_text' => 'Text (eg. Question/Remark/Suggestion)',
				'text_text_default' => 'Default text',
				'attachment_text' => 'Attachment',
			);
			
			return isset($labels[$field]) ? $labels[$field] : '';
		}
		
		public function form($instance) 
		{
			$youbazeSettings = $this->getYouBazeSettings();
			$formSettings = $this->getFormSettings();
			$emailSettings = $this->getEmailSettings();
			$formFields = $this->getFormFields();
			$array = array_merge($youbazeSettings, $formSettings, $emailSettings, $formFields);
			$instance = wp_parse_args((array) $instance, $array);
			$optionvalue = get_option(youbaze_getOptionName());
			
			?><h3>YouBaze settings</h3><?php
			foreach ($youbazeSettings as $key => $value)
			{
				?>
				<p>
					<label for="<?php echo $this->get_field_id($key); ?>"><?php echo youbaze_html($this->getFieldLabel($key));?></label>
					<?php
						if ($key=='salutation')
						{
							?>
							<select class="widefat" id="<?php echo $this->get_field_id($key); ?>" name="<?php echo $this->get_field_name($key); ?>">
								<option value="1"<?php echo isset($instance[$key]) && $instance[$key]==1 ? ' selected="selected"' : '';?>>First name basis</option>
								<option value="2"<?php echo isset($instance[$key]) && $instance[$key]==2 ? ' selected="selected"' : '';?>>Last name basis</option>
							</select>
							<?php
						} else
						{
							?>
							<input type="text" class="widefat" id="<?php echo $this->get_field_id($key); ?>" name="<?php echo $this->get_field_name($key); ?>" value="<?php echo youbaze_html($instance[$key]); ?>" style="width:100%;" />
							<?php
						}
					?>
				</p>
				<?php
			}
			?><h3>Email settings</h3><?php
			foreach ($emailSettings as $key => $value)
			{
				?>
				<p>
					<label for="<?php echo $this->get_field_id($key); ?>"><?php echo youbaze_html($this->getFieldLabel($key));?></label>
					<?php
						if ($key=='confirmation_email')
						{
							?>
							<textarea class="widefat" id="<?php echo $this->get_field_id($key); ?>" name="<?php echo $this->get_field_name($key); ?>" cols="20" rows="16"><?php echo youbaze_html($instance[$key]); ?></textarea>
							<?php
						} else
						{
							?>
							<input type="text" class="widefat" id="<?php echo $this->get_field_id($key); ?>" name="<?php echo $this->get_field_name($key); ?>" value="<?php echo youbaze_html($instance[$key]); ?>" style="width:100%;" />
							<?php
						}
					?>
				</p>
				<?php
			}
			?><h3>Form settings</h3><?php
			foreach ($formSettings as $key => $value)
			{
				?>
				<p>
					<label for="<?php echo $this->get_field_id($key); ?>"><?php echo youbaze_html($this->getFieldLabel($key));?></label>
					<input type="text" class="widefat" id="<?php echo $this->get_field_id($key); ?>" name="<?php echo $this->get_field_name($key); ?>" value="<?php echo youbaze_html($instance[$key]); ?>" style="width:100%;" />
					<?php
					if ($key=='cssclass')
					{
						?>
						<br /><small>Entering extra CSS class(es) allows you to customize each form individually using CSS.</small>
						<?php
					}
					?>
				</p>
				<?php
			}
			?>
			<h3>Form fields</h3>
			<h4>Contact</h4>
			<?php
			foreach ($formFields as $key => $value)
			{
				if (strpos($key, '_enabled')!==false || strpos($key, '_mandatory')!==false) continue;
				$orgkey = $key;
				?>
				<p>
					<label for="<?php echo $this->get_field_id($key); ?>"><?php echo youbaze_html($this->getFieldLabel($key));?></label>
					<?php
						if ($key=='text_text_default')
						{
							?>
							<textarea class="widefat" id="<?php echo $this->get_field_id($key); ?>" name="<?php echo $this->get_field_name($key); ?>" cols="20" rows="16"><?php echo youbaze_html($instance[$key]); ?></textarea>
							<?php
						} else
						{
							$style = $key=='text_text' ? ' style="display: none;"' : '';
							?>
							<input<?php echo $style;?> class="widefat" id="<?php echo $this->get_field_id($key); ?>" name="<?php echo $this->get_field_name($key); ?>" value="<?php echo youbaze_html($instance[$key]); ?>" style="width:100%;" />
							<?php
							if ($key=='text_text') echo '<br />';
						}
					
						if (array_key_exists($key, $formFields) && !in_array($key, array('gender_female_text', 'gender_male_text', 'text_text_default')))
						{
							$key = str_replace('_text', '_enabled', $key);
							?>
							<input<?php echo isset($instance[$key]) && $instance[$key]==1 ? ' checked="checked"' : '';?> id="<?php echo $this->get_field_id($key); ?>" type="checkbox" value="1" name="<?php echo $this->get_field_name($key); ?>" />
							<label for="<?php echo $this->get_field_id($key); ?>">Visible?</label>&nbsp;&nbsp;
							<?php
							$key = str_replace('_enabled', '_mandatory', $key);
							?>
							<input<?php echo isset($instance[$key]) && $instance[$key]==1 ? ' checked="checked"' : '';?> id="<?php echo $this->get_field_id($key); ?>" type="checkbox" value="1" name="<?php echo $this->get_field_name($key); ?>" />
							<label for="<?php echo $this->get_field_id($key); ?>">Mandatory?</label>
							<?php
						}
					?>
				</p>
				<?php
				if ($orgkey=='function_text')
				{
					?><h4>Address</h4><?php
					
				} elseif ($orgkey=='state_text')
				{
					?><h4>P.O. Box</h4><?php
					
				} elseif ($orgkey=='pobox_city_text')
				{
					?><h4>Company</h4><?php
					
				} elseif ($orgkey=='vatnumber_text')
				{
					?><h4>Communications</h4><?php
					
				} elseif ($orgkey=='website_text')
				{
					?><h4>Social media</h4><?php
					
				} elseif ($orgkey=='blog_text')
				{
					?><h4>Text</h4><?php
					
				} elseif ($orgkey=='text_text_default')
				{
					?><h4>Attachment</h4><?php
				}
			}
		}
		
		public function update($new_instance, $old_instance) 
		{
			$youbazeSettings = $this->getYouBazeSettings();
			$formSettings = $this->getFormSettings();
			$emailSettings = $this->getEmailSettings();
			$formFields = $this->getFormFields();
			$array = array_merge($youbazeSettings, $formSettings, $emailSettings, $formFields);
			
			$instance = $old_instance;
			foreach ($array as $key => $value)
				$instance[$key] = $new_instance[$key];
			
			return $instance;
		}
		
		public function youbaze_phpmailer($phpmailer) 
		{
			$phpmailer->isHTML(true);
			$phpmailer->addStringAttachment($this->jsonString, 'contact.youbaze', 'base64', 'application/json', 'attachment');
			if ($this->attachment!==false)
				$phpmailer->AddAttachment($this->attachment['tmp_name'], $this->attachment['name']);
		}
		
		public function widget($args, $instance)
		{
			$optionvalue = get_option(youbaze_getOptionName());
			$checkFields = array();
			$formFields = $this->getFormFields();
			foreach ($formFields as $key => $value)
			{
				if (strpos($key, '_enabled')!==false || strpos($key, '_mandatory')!==false) continue;
				if (in_array($key, array('gender_female_text', 'gender_male_text', 'text_text_default', 'attachment_text'))) continue;
				$enabledKey = str_replace('_text', '_enabled', $key);
				if ($instance[$enabledKey]==0) continue;
				$$key = isset($_POST[$this->id.'-youbaze_submit'], $_POST['youbaze_'.$key]) ? trim($_POST['youbaze_'.$key]) : '';
				$checkFields[] = $key;
			}
			
			$sent = false;
			$error = false;
			$errorfields = array();
			
			if (isset($_POST[$this->id.'-youbaze_submit']) && !(isset($_POST['youbaze_something']) && $_POST['youbaze_something']!=''))
			{
				foreach ($checkFields as $checkField)
				{
					$mandatory = $instance[str_replace('_text', '_mandatory', $checkField)]==1;
					if ($mandatory && $$checkField=='')
					{
						$error = $instance['error_incomplete'];
						$errorfields[] = $checkField;
					}
				}
				if ($error===false && in_array('email_text', $checkFields) && $email_text!='' && !youbaze_validateEmail($email_text))
				{
					$error = $instance['error_invalidemail'];
					$errorfields[] = 'email_text';
				}
				if ($error===false && in_array('email_general_text', $checkFields) && $email_general_text!='' && !youbaze_validateEmail($email_general_text))
				{
					$error = $instance['error_invalidemail'];
					$errorfields[] = 'email_general_text';
				}
				
				if ($error===false)
				{
					if ($instance['attachment_enabled']==1)
					{
						if (isset($_FILES['youbaze_attachment_text']) && is_uploaded_file($_FILES['youbaze_attachment_text']['tmp_name']))
						{
							if (!in_array($_FILES['youbaze_attachment_text']['name'], array('.htaccess', 'contact.youbaze')))
								$this->attachment = $_FILES['youbaze_attachment_text'];
						}
					}
					if ($this->attachment===false && $instance['attachment_mandatory']==1)
					{
						$error = $instance['error_incomplete'];
						$errorfields[] = 'attachment_text';
					}
					
					if ($error===false)
					{
						if (in_array('email_text', $checkFields) && $email_text!='')
						wp_mail($email_text, $instance['subject'], nl2br(youbaze_html($instance['confirmation_email'])), array('Content-Type: text/html; charset=UTF-8', 'From: "'.$instance['sender_name'].'" <'.$instance['sender_email'].'>'));

						$json = array();
						$mailData = array();
						foreach ($this->getYouBazeSettings() as $k => $v)
							$json[$k] = $instance[$k];
						foreach ($checkFields as $checkField)
						{
							if (in_array($checkField, array('text_text', 'subject_text'))) continue;
							$json[str_replace('_text', '', $checkField)] = $$checkField;
							$v = $checkField=='gender_text' ? ($$checkField==1 ? $instance['gender_male_text'] : $instance['gender_female_text']) : $$checkField;
							$mailData[] = $instance[$checkField].': '.$v;
						}
						$this->jsonString = json_encode($json);
						$body = nl2br($text_text).'<br /><br /><hr /><br /><br />'.implode('<br />', $mailData);
						add_action('phpmailer_init', array($this, 'youbaze_phpmailer'));
						$subject = in_array('subject_text', $checkFields) && $subject_text!='' ? $subject_text : $instance['subject'];
						wp_mail($optionvalue['youbaze_email'], $subject, $body, 'From: "'.$instance['sender_name'].'" <'.$instance['sender_email'].'>');
						$this->jsonString = '';
						$this->attachment = false;

						foreach ($checkFields as $checkField)
							$$checkField = '';
						$text_text = $instance['text_text_default'];

						$sent = true;
					}
				}
				
			} else
			{
				$gender = '';
				$text_text = $instance['text_text_default'];
			}
			
			extract($args, EXTR_SKIP);

			echo $before_widget;
			?>			
			<div id="my<?php echo $this->id;?>" class="youbazewidget<?php echo $instance['cssclass']=='' ? '' : ' '.youbaze_html($instance['cssclass'])?>">
				<h2 class="widget-title"><?=youbaze_html($instance['heading'])?></h2>
				<?php
					if ($sent)
					{
						?><div class="youbaze_success"><?php echo youbaze_html($instance['success'])?></div><?php
						
					} elseif ($error!==false)
					{
						?><div class="youbaze_error"><?php echo youbaze_html($error)?></div><?php
					}
				?>				
				<form method="post" enctype="multipart/form-data" action="#my<?php echo $this->id;?>">
					<fieldset>
						<?php 
							foreach ($formFields as $key => $value)
							{
								if (strpos($key, '_enabled')!==false || strpos($key, '_mandatory')!==false) continue;
								if (in_array($key, array('gender_female_text', 'gender_male_text', 'text_text_default'))) continue;
								$enabledKey = str_replace('_text', '_enabled', $key);
								if ($instance[$enabledKey]==0) continue;
								$mandatory = $instance[str_replace('_text', '_mandatory', $key)]==1;
								$errorclass = in_array($key, $errorfields) ? ' class="youbaze_errorfield text_input"' : ' class="text_input"';
								?>
								<div class="youbaze_section <?php echo $key?>">
									<p>
										<label for="youbaze_<?php echo youbaze_html($key);?>"><?php echo youbaze_html($instance[$key]);?><?php echo $mandatory ? '<i class="required">*</i>' : ''?></label>
										<?php
											if ($key=='text_text')
											{
												?>
												<textarea<?php echo $errorclass;?> name="youbaze_<?php echo youbaze_html($key);?>" id="youbaze_<?php echo youbaze_html($key);?>" rows="12"><?php echo youbaze_html($$key);?></textarea>
												<?php

											} elseif ($key=='gender_text')
											{
												?>
												<select<?php echo $errorclass;?> name="youbaze_<?php echo youbaze_html($key);?>" id="youbaze_<?php echo youbaze_html($key);?>">
													<option value="">&nbsp;</option>
													<option value="1"<?php echo $$key==1 ? ' selected="selected"' : '';?>><?php echo youbaze_html($instance['gender_male_text']);?></option>
													<option value="2"<?php echo $$key==2 ? ' selected="selected"' : '';?>><?php echo youbaze_html($instance['gender_female_text']);?></option>
												</select>
												<?php
											} elseif ($key=='attachment_text')
											{
												?>
												<input<?php echo $errorclass;?> type="file" name="youbaze_<?php echo youbaze_html($key);?>" id="youbaze_<?php echo youbaze_html($key);?>" />
												<?php
											} else
											{
												?>
												<input<?php echo $errorclass;?> type="text" name="youbaze_<?php echo youbaze_html($key);?>" id="youbaze_<?php echo youbaze_html($key);?>" value="<?php echo youbaze_html($$key);?>" />
												<?php
											}
										?>
									</p>
								</div>
								<?php
							}
						?>
						<input type="text" name="youbaze_something" value="" style="width: 0px; height: 0px; padding: 0px; margin: 0px;" />
						<div class="youbaze_buttons">
							<input class="button" type="submit" name="<?php echo $this->id;?>-youbaze_submit" value="<?php echo youbaze_html($instance['submit']);?>" />
						</div>
					</fieldset>
				</form>
			</div>
			<?php
			echo $after_widget;
		}
	}
	
	add_action('widgets_init', create_function('', 'return register_widget("YouBazeWidget");'));
?>