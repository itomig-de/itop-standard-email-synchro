<?php
class MailInboxStandard extends MailInboxBase
{
	protected static $aUndesiredSubjectPatterns = null;
	
	public static function Init()
	{
		$aParams = array
		(
			"category" => "searchable,view_in_gui,bizmodel",
			"key_type" => "autoincrement",
			"name_attcode" => array("login"),
			"state_attcode" => "",
			"reconc_keys" => array('server', 'login', 'protocol', 'mailbox', 'port'),
			"db_table" => "mailinbox_standard",
			"db_key_field" => "id",
			"db_finalclass_field" => "realclass",
			"display_template" => "",
			'icon' => utils::GetAbsoluteUrlModulesRoot().basename(dirname(__FILE__)).'/images/mailbox.png',
		);
		MetaModel::Init_Params($aParams);
		MetaModel::Init_InheritAttributes();
		MetaModel::Init_AddAttribute(new AttributeEnum("behavior", array("allowed_values"=>new ValueSetEnum('create_only,update_only,both'), "sql"=>"behavior", "default_value"=>'both', "is_null_allowed"=>false, "depends_on"=>array())));
		MetaModel::Init_AddAttribute(new AttributeEnum("target_class", array("allowed_values"=> new ValueSetEnum('Incident,UserRequest,Change,RoutineChange,NormalChange,EmergencyChange,Problem'), "sql"=>"target_class", "default_value"=>'UserRequest', "is_null_allowed"=>false, "depends_on"=>array())));
		MetaModel::Init_AddAttribute(new AttributeText("ticket_default_values", array("allowed_values"=>null, "sql"=>"ticket_default_values", "default_value"=>null, "is_null_allowed"=>true, "depends_on"=>array())));
		MetaModel::Init_AddAttribute(new AttributeString("ticket_default_title", array("allowed_values"=>null, "sql"=>"ticket_default_title", "default_value"=>null, "is_null_allowed"=>true, "depends_on"=>array())));
		MetaModel::Init_AddAttribute(new AttributeString("title_pattern", array("allowed_values"=>null, "sql"=>"title_pattern", "default_value"=>null, "is_null_allowed"=>true, "depends_on"=>array())));
		MetaModel::Init_AddAttribute(new AttributeEnum("unknown_caller_behavior", array("allowed_values"=>new ValueSetEnum('create_contact,reject_email'), "sql"=>"unknown_caller_behavior", "default_value"=>'reject_email', "is_null_allowed"=>false, "depends_on"=>array())));
		MetaModel::Init_AddAttribute(new AttributeText("caller_default_values", array("allowed_values"=>null, "sql"=>"caller_default_values", "default_value"=>null, "is_null_allowed"=>true, "depends_on"=>array())));
		MetaModel::Init_AddAttribute(new AttributeEnum("error_behavior", array("allowed_values"=>new ValueSetEnum('delete,mark_as_error'), "sql"=>"error_behavior", "default_value"=>'mark_as_error', "is_null_allowed"=>false, "depends_on"=>array())));
		MetaModel::Init_AddAttribute(new AttributeEmailAddress("notify_errors_to", array("allowed_values"=>null, "sql"=>"notify_errors_to", "default_value"=>"", "is_null_allowed"=>true, "depends_on"=>array())));
		MetaModel::Init_AddAttribute(new AttributeEmailAddress("notify_errors_from", array("allowed_values"=>null, "sql"=>"notify_errors_from", "default_value"=>"", "is_null_allowed"=>true, "depends_on"=>array())));
		MetaModel::Init_AddAttribute(new AttributeEnum("trace", array("allowed_values"=>new ValueSetEnum('yes,no'), "sql"=>"trace", "default_value"=>'no', "is_null_allowed"=>false, "depends_on"=>array())));
		MetaModel::Init_AddAttribute(new AttributeLongText("debug_trace", array("allowed_values"=>null, "sql"=>"debug_trace", "default_value"=>null, "is_null_allowed"=>true, "depends_on"=>array())));
		MetaModel::Init_AddAttribute(new AttributeEnum("email_storage", array("allowed_values"=>new ValueSetEnum('keep,delete'), "sql"=>"email_storage", "default_value"=>'keep', "is_null_allowed"=>false, "depends_on"=>array())));
		MetaModel::Init_AddAttribute(new AttributeEnum("import_additional_contacts", array("allowed_values"=>new ValueSetEnum('never,only_on_creation,only_on_update,always'), "sql"=>"import_additional_contacts", "default_value"=>'never', "is_null_allowed"=>false, "depends_on"=>array())));
		MetaModel::Init_AddAttribute(new AttributeText("stimuli", array("allowed_values"=>null, "sql"=>"stimuli", "default_value"=>null, "is_null_allowed"=>true, "depends_on"=>array())));
		
		// Display lists
		// Display lists
		MetaModel::Init_SetZListItems('details', array(
											'col:col0' => array(
													'fieldset:MailInbox:Server' => array('server', 'login', 'password', 'protocol', 'port', 'mailbox', 'active', 'trace'),
													'fieldset:MailInbox:Errors' => array('error_behavior', 'notify_errors_to', 'notify_errors_from'),
											),
											'col:col1' => array(
													'fieldset:MailInbox:Behavior' => array( 'behavior', 'email_storage', 'target_class', 'ticket_default_values', 'ticket_default_title', 'title_pattern', 'stimuli'),
													'fieldset:MailInbox:Caller' => array('unknown_caller_behavior', 'caller_default_values'),
													'fieldset:MailInbox:OtherContacts' => array('import_additional_contacts'),
											),
										)); // Attributes to be displayed for the complete details
		MetaModel::Init_SetZListItems('list', array('server', 'mailbox','protocol', 'active')); // Attributes to be displayed for a list
		MetaModel::Init_SetZListItems('standard_search', array('server', 'login', 'mailbox','protocol', 'active')); // Attributes to be displayed in the search form
	}

	/**
	 * Add an extra tab showing the debug trace
	 * @see cmdbAbstractObject::DisplayBareRelations()
	 */
	function DisplayBareRelations(WebPage $oPage, $bEditMode = false)
	{
		parent::DisplayBareRelations($oPage, $bEditMode);
		if (!$bEditMode)
		{
			$oPage->SetCurrentTab(Dict::S('MailInboxStandard:DebugTrace'));
			$sAjaxUrl = addslashes(utils::GetAbsoluteUrlModulesRoot().basename(dirname(__FILE__)).'/ajax.php');
			$iId = $this->GetKey();
			if ($this->Get('trace') == 'yes')
			{
				$oPage->add('<p><button type="button" id="debug_trace_refresh">'.Dict::S(Dict::S('UI:Button:Refresh')).'</button></p>');
				$oPage->add('<div id="debug_trace_output"></div>');
				$oPage->add_ready_script(
<<<EOF
$('#debug_trace_refresh').click(function() {
	$('#debug_trace_output').html('<img src="../images/indicator.gif"/>');
	$.post('$sAjaxUrl', {operation: 'debug_trace', id: $iId }, function(data) {
		$('#debug_trace_output').html(data);
	});
});
$('#debug_trace_refresh').trigger('click');
EOF
				);
			}
			else
			{
				$oPage->add('<div id="debug_trace_output"><p>'.Dict::S('MailInboxStandard:DebugTraceNotActive').'</p></div>');
			}
		}
	}
		
	/**
	 * Debug trace: activated/disabled by the configuration flag set for the base module...
	 * @param string $sText
	 */
	protected function Trace($sText)
	{
		parent::Trace($sText);
		$iMaxTraceLength = 500*1024; // Maximum size of the Trace to keep in the database...
		
		if ($this->Get('trace') == 'yes')
		{
			$sStampedText = date('Y-m-d H:i:s').' - '.$sText."\n";
			$this->Set('debug_trace', substr($this->Get('debug_trace').$sStampedText, -$iMaxTraceLength));

			// Creating a CMDBChange is no longer needed in 2.0, but let's keep doing it for compatibility with 1.x
			$oMyChange = MetaModel::NewObject("CMDBChange");
			$oMyChange->Set("date", time());
			$sUserString = CMDBChange::GetCurrentUserName();
			$oMyChange->Set("userinfo", $sUserString);
			$iChangeId = $oMyChange->DBInsert();
			$this->DBUpdateTracked($oMyChange);
		}
	}
	
	protected function RecordAttChanges(array $aValues, array $aOrigValues)
	{
		// Do NOT record the changes on the 'debug trace' attribute
		unset($aValues['debug_trace']);
		parent::RecordAttChanges($aValues, $aOrigValues);
	}
	
	/**
	 * Initial dispatching of an incoming email: determines what to do with the email
	 * @param EmailReplica $oEmailReplica The EmailReplica associated with the email. A new replica (i.e. not yet in DB) one for new emails
	 * @return int An action code from EmailProcessor
	 */
	public function DispatchEmail(EmailReplica $oEmailReplica)
	{
		return parent::DispatchEmail($oEmailReplica);
	}
	
	/**
	 * Process an new (unread) incoming email
	 * @param EmailSource $oSource The source from which this email was read
	 * @param int $index The index of the message in the source
	 * @param EmailMessage $oEmail The decoded email
	 * @return Ticket The ticket created or updated in response to the email
	 */
	public function ProcessNewEmail(EmailSource $oSource, $index, EmailMessage $oEmail)
	{		
		$this->Trace("Processing new eMail (index = $index)");
		$oTicket = null;
		if ($this->IsUndesired($oEmail))
		{
			$this->HandleError($oEmail, 'undesired_message', $oEmail->oRawEmail);
			return null;
		}

		// Search if the caller email is an existing contact in iTop
		$oCaller = $this->FindCaller($oEmail);
		
		// Check whether we need to create a new ticket or to update an existing one
		$oTicket = $this->GetRelatedTicket($oEmail);

		if (($oCaller == null) && ($oTicket == null))
		{
			// Cannot create a ticket if the caller is unknown
			return null;
		}
		
		switch($this->Get('behavior'))
		{
			case 'create_only':
			$oTicket = $this->CreateTicketFromEmail($oEmail, $oCaller);
			break;
			
			case 'update_only':
			if (!is_object($oTicket))
			{
				// No ticket associated with the incoming email, nothing to update, reject the email
				$this->HandleError($oEmail, 'nothing_to_update', $oEmail->oRawEmail);
			}
			else
			{
				// Update the ticket with the incoming eMail
				$this->UpdateTicketFromEmail($oTicket, $oEmail, $oCaller);
			}
			break;
			
			default: // both: update or create as needed
			if (!is_object($oTicket))
			{
				// Let's create a new ticket
				$oTicket = $this->CreateTicketFromEmail($oEmail, $oCaller);
			}
			else
			{
				// Update the ticket with the incoming eMail
				$this->UpdateTicketFromEmail($oTicket, $oEmail, $oCaller);
			}
			break;			
		}
		
		return $oTicket;
	}
	
	/**
	 * Search if the caller email is an existing contact in iTop, if not may create it
	 * depending on the mailinbox setting.
	 * {@inheritDoc}
	 * @see MailInboxBase::FindCaller()
	 */
	protected function FindCaller(EmailMessage $oEmail)
	{
		$oCaller = null;
		$sContactQuery = 'SELECT Contact WHERE email = :email';
		$oSet = new DBObjectSet(DBObjectSearch::FromOQL($sContactQuery), array(), array('email' => $oEmail->sCallerEmail));
		$sAdditionalDescription = '';
		switch($oSet->Count())
		{
			case 1:
			// Ok, the caller was found in iTop
			$oCaller = $oSet->Fetch();
			break;
			
			case 0:
			switch($this->Get('unknown_caller_behavior'))
			{
				case 'reject_email':
				$this->Trace('No contact found for the email address "'.$oEmail->sCallerEmail.'", the ticket will NOT be created');
				$this->HandleError($oEmail, 'unknown_contact', $oEmail->oRawEmail);
				return null;
				break;
				
				case 'create_contact':
				default:
				$this->Trace("Creating a new Person for the email: {$oEmail->sCallerEmail}");
				$oCaller = new Person();
				$oCaller->Set('email', $oEmail->sCallerEmail);
				$sDefaultValues = $this->Get('caller_default_values');
				$aDefaults = explode("\n", $sDefaultValues);
				$aDefaultValues = array();
				foreach($aDefaults as $sLine)
				{
					if (preg_match('/^([^:]+):(.*)$/', $sLine, $aMatches))
					{
						$sAttCode = trim($aMatches[1]);
						$sValue = trim($aMatches[2]);
						$aDefaultValues[$sAttCode] = $sValue;
					}
				}
				$this->InitObjectFromDefaultValues($oCaller, $aDefaultValues);
				try
				{
					// Creating a CMDBChange is no longer needed in 2.0, but let's keep doing it for compatibility with 1.x
					$oMyChange = MetaModel::NewObject("CMDBChange");
					$oMyChange->Set("date", time());
					$sUserString = CMDBChange::GetCurrentUserName();
					$oMyChange->Set("userinfo", $sUserString);
					$iChangeId = $oMyChange->DBInsert();
					$oCaller->DBInsertTracked($oMyChange);					
				}
				catch(Exception $e)
				{
					$this->Trace('Failed to create a Person for the email address "'.$oEmail->sCallerEmail.'".');
					$this->Trace($e->getMessage());
					$this->HandleError($oEmail, 'failed_to_create_contact', $oEmail->oRawEmail);
					return null;
				}
				
			}			
			break;
			
			default:
			$this->Trace('Found '.$oSet->Count().' callers with the same email address "'.$oEmail->sCallerEmail.'", the first one will be used...');
			// Multiple callers with the same email address !!!
			$oCaller = $oSet->Fetch();
		}
		
		return $oCaller;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see MailInboxBase::GetRelatedTicket()
	 */
	protected function GetRelatedTicket(EmailMessage $oEmail)
	{
		// First check if there is any iTop object mentioned in the headers of the eMail
		$oTicket = parent::GetRelatedTicket($oEmail);
		
		if ($oTicket == null)
		{
			// No associated ticket found by parsing the headers, check
			// if the subject does not match a specific pattern
			$sPattern = $this->FixPattern($this->Get('title_pattern'));
			if(($sPattern != '') && (preg_match($sPattern, $oEmail->sSubject, $aMatches)))
			{
				$iTicketId = 0;
				sscanf($aMatches[1], '%d', $iTicketId);
				$this->Trace("iTop Simple Email Synchro: Retrieving ticket ".$iTicketId." (match by subject pattern)...");
				$oTicket = MetaModel::GetObject('Ticket', $iTicketId, false);
			}
		}		
		
		return $oTicket;
	}
	
	/**
	 * Actual creation of the ticket from the incoming email. Overload this method
	 * to implement your own behavior, if needed
	 * @param EmailMessage $oEmail The decoded incoming email
	 * @param Contact $oCaller The contact corresponding to the "From" email address
	 * @return Ticket the created ticket or null in case of failure
	 */
	public function CreateTicketFromEmail(EmailMessage $oEmail, Contact $oCaller)
	{
		// In case of error (exception...) set the behavior
		if ($this->Get('error_behavior') == 'delete')
		{
			$this->SetNextAction(EmailProcessor::DELETE_MESSAGE); // Remove the message from the mailbox
		}
		else
		{
			$this->SetNextAction(EmailProcessor::MARK_MESSAGE_AS_ERROR); // Keep the message in the mailbox, but marked as error
		}
		$this->Trace("Creating a new Ticket from eMail '".$oEmail->sSubject."'");
		if (!MetaModel::IsValidClass($this->Get('target_class')))
		{
			throw new Exception('Invalid "ticket_class" configured: "'.$this->Get('target_class').'" is not a valid class. Cannot create such an object.');
		}
		$oTicket = MetaModel::NewObject($this->Get('target_class'));
		$oTicket->Set('org_id', $oCaller->Get('org_id'));
		if (MetaModel::IsValidAttCode(get_class($oTicket), 'caller_id'))
		{
			$oTicket->Set('caller_id', $oCaller->GetKey());
		}
		if (MetaModel::IsValidAttCode(get_class($oTicket), 'origin'))
		{
			$oTicket->Set('origin', 'mail');
		}
		if ($oEmail->sSubject == '')
		{
			$sDefaultSubject = ($this->Get('ticket_default_title') == '') ? Dict::S('MailInbox:NoSubject') : $this->Get('ticket_default_title');
			$this->Trace("The incoming email has no subject, the ticket's title will be set to: '$sDefaultSubject'");
			$oTicket->Set('title', $sDefaultSubject);
		}
		else
		{
			$oAttDef = MetaModel::GetAttributeDef(get_class($oTicket), 'title');
			$iMaxSize = $oAttDef->GetMaxSize();
			$oTicket->Set('title', substr($oEmail->sSubject, 0, $iMaxSize));
		}
		
		$aIgnoredAttachments = array();
		
		// Insert the remaining attachments so that we know their ID and can reference them in the message's body
		$aAddedAttachments = $this->AddAttachments($oTicket, $oEmail, true, $aIgnoredAttachments);  // Cannot insert them for real since the Ticket is not saved yet (we don't know its ID)
																									// we'll have to call UpdateAttachments once the ticket is properly saved
		$oAttDef = MetaModel::GetAttributeDef(get_class($oTicket), 'description');
		$bForPlainText = true; // Target format is plain text (by default)
		if ($oAttDef instanceof AttributeHTML)
		{
			// Target format is HTML
			$bForPlainText = false;
		}
		else if ($oAttDef instanceof AttributeText)
		{
			$aParams = $oAttDef->GetParams();
			if (array_key_exists('format', $aParams) && ($aParams['format'] == 'html'))
			{
				// Target format is HTML
				$bForPlainText = false;
			}
		}
		$this->Trace("Target format for 'description': ".($bForPlainText ? 'text/plain' : 'text/html'));
		$this->Trace("Email body format: ".$oEmail->sBodyFormat);
		
		$sTicketDescription = $this->BuildDescription($oEmail, $aAddedAttachments, $aIgnoredAttachments, $bForPlainText);

		$iMaxSize = $oAttDef->GetMaxSize();
		$bTextTruncated = false;
		if (strlen($sTicketDescription) > $iMaxSize)
		{
			$oEmail->aAttachments[] = array('content' => $sTicketDescription, 'filename' => ($bForPlainText ? 'original message.txt' : 'original message.html'), 'mimeType' => ($bForPlainText ? 'text/plain' : 'text/html'));
		}
		
		$oTicket->Set('description', $this->FitTextIn($sTicketDescription, $iMaxSize - 1000)); // Keep some room just in case...
		
		// Default values
		$sDefaultValues = $this->Get('ticket_default_values');
		$aDefaults = explode("\n", $sDefaultValues);
		$aDefaultValues = array();
		foreach($aDefaults as $sLine)
		{
			if (preg_match('/^([^:]+):(.*)$/', $sLine, $aMatches))
			{
				$sAttCode = trim($aMatches[1]);
				$sValue = trim($aMatches[2]);
				$aDefaultValues[$sAttCode] = $sValue;
			}
		}
		$this->InitObjectFromDefaultValues($oTicket, $aDefaultValues);
		
		if (($this->Get('import_additional_contacts') == 'always') || ($this->Get('import_additional_contacts') == 'only_on_creation'))
		{
			$this->AddAdditionalContacts($oTicket, $oEmail);
		}
		
		$this->BeforeInsertTicket($oTicket, $oEmail, $oCaller);
		$oTicket->DBInsert();
		$this->Trace("Ticket ".$oTicket->GetName()." created.");
		$this->AfterInsertTicket($oTicket, $oEmail, $oCaller, $aAddedAttachments);
		
		return $oTicket;
	}
	
	/**
	 * Build the 'description' of the ticket when creating a new ticket
	 * @param EmailMessage $oEmail The incoming Email
	 * @param bool $bForPlainText True if the desired output format is plain text, false if HTML
	 * @return string
	 */
	protected function BuildDescription(EmailMessage $oEmail, $aAddedAttachments, $aIgnoredAttachments, $bForPlainText)
	{
		$sTicketDescription = '';
		if ($oEmail->sBodyFormat == 'text/html')
		{
			// Original message is in HTML
			$this->Trace("Managing inline images...");
			$sTicketDescription = $this->ManageInlineImages($oEmail->sBodyText, $aAddedAttachments, $aIgnoredAttachments, $bForPlainText);
			if ($bForPlainText)
			{
				$this->Trace("Converting HTML to text using utils::HtmlToText...");
				$sTicketDescription = utils::HtmlToText($sBodyText);
			}
		}
		else
		{
			// Original message is in plain text
			$sTicketDescription = utils::TextToHtml($oEmail->sBodyText);
			if (!$bForPlainText)
			{
				$this->Trace("Converting text to HTML using utils::TextToHtml...");
				$sTicketDescription = utils::TextToHtml($oEmail->sBodyText);
			}
		}

		if (empty($sTicketDescription))
		{
			$sTicketDescription = 'No description provided.';
		}
		
		return $sTicketDescription;
	}
	
	/**
	 * Add the contacts in To: or CC: as additional contacts to the ticket (if they exist in the DB)
	 * @param Ticket $oTicket
	 * @param EmailMessage $oEmail
	 */
	protected function AddAdditionalContacts(Ticket $oTicket, EmailMessage $oEmail)
	{
		$oContactsSet = $oTicket->Get('contacts_list');
		$aExistingContacts = array();
		while($oLnk = $oContactsSet->Fetch())
		{
			$aExistingContacts[$oLnk->Get('contact_id')] = true;
		}
		$aAdditionalContacts = $oEmail->aTos + $oEmail->aCCs; // Take both the To: and CC:
		foreach($aAdditionalContacts as $aInfo)
		{
			$sCallerEmail = $oTicket->Get('caller_id->email');
			// Exclude the caller from the additional contacts
			if ($aInfo['email'] != $sCallerEmail)
			{
				$oContact = $this->FindAdditionalContact($aInfo['email']);
				if (($oContact != null) && !array_key_exists($oContact->GetKey(), $aExistingContacts))
				{
					$oLnk = new lnkContactToTicket();
					$oLnk->Set('contact_id', $oContact->GetKey());
					$oContactsSet->AddObject($oLnk);
				}
			}
			else
			{
				$this->Trace('Skipping "'.$sEmail.'" from the email address in To/CC since it is the same as the caller\'s email.');
			}
		}
		$oTicket->Set('contacts_list', $oContactsSet);
	}
	
	/**
	 * Search if the CC email is an existing contact in iTop, if so return it, otherwise ignore it
	 * @param $sEmail string The email address to seach
	 * @return Contact | null
	 */
	protected function FindAdditionalContact($sEmail)
	{
		$oContact = null;
		$sContactQuery = 'SELECT Contact WHERE email = :email';
		$oSet = new DBObjectSet(DBObjectSearch::FromOQL($sContactQuery), array(), array('email' => $sEmail));
		switch($oSet->Count())
		{
			case 1:
			// Ok, the caller was found in iTop
			$oContact = $oSet->Fetch();
			$this->Trace('Found Contact::'.$oContact->GetKey().' ('.$oContact->GetName().') from the email address in To/CC "'.$sEmail.'".');
			break;
			
			case 0:
			$this->Trace('No contact found with the email address in CC "'.$sEmail.'", email address ignored.');
			break;
			
			default:
			$this->Trace('Found '.$oSet->Count().' contacts with the same email address in To/CC "'.$sEmail.'", the first one will be used...');
			// Multiple contacts with the same email address !!!
			$oCaller = $oSet->Fetch();
		}
		return $oContact;
	}
	
	/**
	 * Handler called just before inserting the ticket into the database
	 * Overload this method to adjust the values of the ticket at your will
	 * @param Ticket $oTicket
	 * @param EmailMessage $oEmail
	 * @param Contact $oCaller
	 */
	protected function BeforeInsertTicket(Ticket $oTicket, EmailMessage $oEmail, Contact $oCaller)
	{
		// Default implementation: do nothing
	}
	
	/**
	 * Finalize the processing after the insertion of the ticket in the database
	 * @param Ticket $oTicket The ticket being written
	 * @param EmailMessage $oEmail The source email
	 * @param Contact $oCaller The caller for this ticket, as passed to CreateTicket
	 * @param array $aAddedAttachments The array of attachments added to the ticket
	 */
	protected function AfterInsertTicket(Ticket $oTicket, EmailMessage $oEmail, Contact $oCaller, $aAddedAttachments)
	{
		// Process attachments
		$this->UpdateAttachments($aAddedAttachments, $oTicket); // Now update the attachments since we know the ID of the ticket
		
		// Shall we delete the source email immediately?
		if ($this->Get('email_storage') == 'delete')
		{
			// Remove the processed message from the mailbox
			$this->Trace("Ticket created, deleting the source eMail '".$oEmail->sSubject."'");
			$this->SetNextAction(EmailProcessor::DELETE_MESSAGE);		
		}
		else
		{
			// Keep the message in the mailbox
			$this->SetNextAction(EmailProcessor::NO_ACTION);		
		}		
	}
	
	/**
	 * Actual update of a ticket from the incoming email. Overload this method
	 * to implement your own behavior, if needed
	 * @param Ticket $oTicket The ticket to update
	 * @param EmailMessage $oEmail The decoded incoming email
	 * @param Contact $oCaller The contact corresponding to the "From" email address
	 * @return void
	 */
	public function UpdateTicketFromEmail(Ticket $oTicket, EmailMessage $oEmail, Contact $oCaller)
	{
		// In case of error (exception...) set the behavior
		if ($this->Get('error_behavior') == 'delete')
		{
			$this->SetNextAction(EmailProcessor::DELETE_MESSAGE); // Remove the message from the mailbox
		}
		else
		{
			$this->SetNextAction(EmailProcessor::MARK_MESSAGE_AS_ERROR); // Keep the message in the mailbox, but marked as error
		}		
		
		// Check that the ticket is of the expected class
		if (!is_a($oTicket, $this->Get('target_class')))
		{
			$this->Trace("iTop Simple Email Synchro: Error: the incoming email refers to the ticket ".$oTicket->GetName()." of class ".get_class($oTicket).", but this mailbox is configured to process only tickets of class ".$this->Get('target_class'));
			$this->SetNextAction(EmailProcessor::MARK_MESSAGE_AS_ERROR); // Keep the message in the mailbox, but marked as error
			return;
		}
		
		// Try to extract what's new from the message's body
		$this->Trace("iTop Simple Email Synchro: Updating the iTop ticket ".$oTicket->GetName()." from eMail '".$oEmail->sSubject."'");

		
		// Process attachments
		$aIgnoredAttachments = array();
		$aAddedAttachments = $this->AddAttachments($oTicket, $oEmail, true, $aIgnoredAttachments);
		
		$sCaseLogEntry = $this->BuildCaseLogEntry($oEmail, $aAddedAttachments, $aIgnoredAttachments);
		
		$this->Trace($oEmail->sTrace);
		// Write the log on behalf of the caller
		$sCallerName = $oEmail->sCallerName;
		if (empty($sCallerName))
		{
			$sCallerName = $oEmail->sCallerEmail;
		}
					
		// Determine which field to update
		$sAttCode = 'public_log';
		$aAttCodes = MetaModel::GetModuleSetting('itop-standard-email-synchro', 'ticket_log', array('UserRequest' => 'public_log', 'Incident' => 'public_log'));
		if (array_key_exists(get_class($oTicket), $aAttCodes))
		{
			$sAttCode = $aAttCodes[get_class($oTicket)];
		}
		
		$oLog = $oTicket->Get($sAttCode);
		$oLog->AddLogEntry($sCaseLogEntry, $sCallerName);
		$oTicket->Set($sAttCode, $oLog);
		
		if (($this->Get('import_additional_contacts') == 'always') || ($this->Get('import_additional_contacts') == 'only_on_update'))
		{
			$this->AddAdditionalContacts($oTicket, $oEmail);
		}
		$this->BeforeUpdateTicket($oTicket, $oEmail, $oCaller);
		$oTicket->DBUpdate();			
		$this->Trace("Ticket ".$oTicket->GetName()." updated.");
		$this->AfterUpdateTicket($oTicket, $oEmail, $oCaller);
				
		return $oTicket;		
	}
	
	/**
	 * Build the text/html to be inserted in the case log when the ticket is updated
	 * Starting with iTop 2.3.0, the format is always HTML
	 * @param EmailMessage $oEmail
	 * @return string The HTML text to be inserted in the case log
	 */
	protected function BuildCaseLogEntry(EmailMessage $oEmail, $aAddedAttachments, $aIgnoredAttachments)
	{
		$sCaseLogEntry = '';
		$this->Trace("Email body format: ".$oEmail->sBodyFormat);
		if ($oEmail->sBodyFormat == 'text/html')
		{
			$this->Trace("Extracting the new part using GetNewPartHTML...");
			$sCaseLogEntry = $oEmail->GetNewPartHTML($oEmail->sBodyText);
			if (strip_tags($sCaseLogEntry) == '')
			{
				// No new part (only blank tags)... we'd better use the whole text of the message
				$sCaseLogEntry = $oEmail->sBodyText;
			}
			$this->Trace("Managing inline images...");
			$sCaseLogEntry = $this->ManageInlineImages($sCaseLogEntry, $aAddedAttachments, $aIgnoredAttachments, false /* $bForPlainText */);
		}
		else
		{
			$this->Trace("Extracting the new part using GetNewPart...");
			$sCaseLogEntry = $oEmail->GetNewPart($oEmail->sBodyText, $oEmail->sBodyFormat); // GetNewPart always returns a plain text version of the message
		}
		return $sCaseLogEntry;
	}

	/**
	 * Handler called before updating a ticket in the database
	 * Overload this method to alter the ticket at your will
	 * @param Ticket $oTicket
	 * @param EmailMessage $oEmail
	 * @param Contact $oCaller
	 */
	protected function BeforeUpdateTicket(Ticket $oTicket, EmailMessage $oEmail, Contact $oCaller)
	{
		// Default implementation: do nothing
	}
	
	/**
	 * Read the configuration in the 'stimuli' field (format: <state_code>:<stimulus_code>, one per line)
	 * and apply the corresponding stimulus according to the current state of the ticket
	 * @param ticket $oTicket
	 */
	protected function ApplyConfiguredStimulus(ticket $oTicket)
	{
		$sConf = $this->Get('stimuli');
		$aConf = explode("\n", $sConf);
		$aStateToStimulus = array();
		foreach($aConf as $sLine)
		{
			if (preg_match('/^([^:]+):(.*)$/', $sLine, $aMatches))
			{
				$sState = trim($aMatches[1]);
				$sStimulus = trim($aMatches[2]);
				$aStateToStimulus[$sState] = $sStimulus;
			}
			else
			{
				$this->Trace('Invalid line in the "stimuli" configuration: "'.$sLine.'". The expected format for each line is <state_code>:<stimulus_code>');
			}
		}
		if (array_key_exists($oTicket->GetState(), $aStateToStimulus))
		{
			$sStimulusCode = $aStateToStimulus[$oTicket->GetState()];
			$this->Trace('Applying the stimulus: '.$sStimulusCode.' for the ticket in state: '.$oTicket->GetState());
			try
			{
				$oTicket->ApplyStimulus($sStimulusCode);
			}
			catch(Exception $e)
			{
				$this->Trace('ApplyStimulus failed: '.$e->getMessage());
			}
		}
	}
	
	/**
	 * Finalize the processing after the update of the ticket in the database
	 * @param Ticket $oTicket The ticket being written
	 * @param EmailMessage $oEmail The source email
	 * @param Contact $oCaller The caller for this ticket, as passed to UpdateTicket
	 */
	protected function AfterUpdateTicket(Ticket $oTicket, EmailMessage $oEmail, Contact $oCaller)
	{
		// If there are any TriggerOnMailUpdate defined, let's activate them
		$aClasses = MetaModel::EnumParentClasses(get_class($oTicket), ENUM_PARENT_CLASSES_ALL);
		$sClassList = implode(", ", CMDBSource::Quote($aClasses));
		$oSet = new DBObjectSet(DBObjectSearch::FromOQL("SELECT TriggerOnMailUpdate AS t WHERE t.target_class IN ($sClassList)"));
		while ($oTrigger = $oSet->Fetch())
		{
			$oTrigger->DoActivate($oTicket->ToArgs('this'));
		}

		// Apply a stimulus if needed, will write the ticket to the database, may launch triggers, etc...
		$this->ApplyConfiguredStimulus($oTicket);
		
		// Shall we keep the email or delete it immediately?
		if ($this->Get('email_storage') == 'delete')
		{
			// Remove the processed message from the mailbox
			$this->Trace("Ticket updated, deleting the source eMail '".$oEmail->sSubject."'");
			$this->SetNextAction(EmailProcessor::DELETE_MESSAGE);		
		}
		else
		{
			// Keep the message in the mailbox
			$this->SetNextAction(EmailProcessor::NO_ACTION);		
		}
	}
	
	protected function ManageInlineImages($sBodyText, $aAddedAttachments, $aIgnoredAttachments, $bForPlainText = true)
	{
		// Search for inline images: i.e. <img tags containing an src="cid:...."
		if (preg_match_all('/<img[^>]+src="cid:([^"]+)"/i', $sBodyText, $aMatches, PREG_OFFSET_CAPTURE))
		{
			$aInlineImages = array();
			foreach ($aMatches[0] as $idx => $aInfo)
			{
				$aInlineImages[$idx] = array(
					'position' => $aInfo[1]
				);
			}
			foreach ($aMatches[1] as $idx => $aInfo)
			{
				$sCID = $aInfo[0];
				if (!array_key_exists($sCID, $aAddedAttachments) && !array_key_exists($sCID, $aIgnoredAttachments))
				{
					$this->Trace("Info: inline image: $sCID not found as an attachment. Ignored.");
				}
				else if (array_key_exists($sCID, $aAddedAttachments))
				{
					$aInlineImages[$idx]['cid'] = $sCID;
					$this->Trace("Inline image cid:$sCID stored as ".get_class($aAddedAttachments[$sCID])."::".$aAddedAttachments[$sCID]->GetKey());
				}
			}
			if (!defined('ATTACHMENT_DOWNLOAD_URL'))
			{
				define('ATTACHMENT_DOWNLOAD_URL', 'pages/ajax.render.php?operation=download_document&class=Attachment&field=contents&id=');
			}
			if ($bForPlainText)
			{
				// The target form is text/plain, so the HTML tags will be stripped
				// Insert the URLs to the attachments, just before the <img tag so that the hyperlink remains (as plain text) at the right position
				// when the HTML tags will be stripped
				// Start from the end of the text to preserve the positions of the <img tags AFTER the insertion
				$sWholeText = $sBodyText;
				$idx = count($aInlineImages);
				while ($idx > 0)
				{
					$idx --;
					if (array_key_exists('cid', $aInlineImages[$idx]))
					{
						$sBefore = substr($sWholeText, 0, $aInlineImages[$idx]['position']);
						$sAfter = substr($sWholeText, $aInlineImages[$idx]['position']);
						$oAttachment = $aAddedAttachments[$aInlineImages[$idx]['cid']];
						$sUrl = utils::GetAbsoluteUrlAppRoot().ATTACHMENT_DOWNLOAD_URL.$oAttachment->GetKey();
						$sWholeText = $sBefore.' '.$sUrl.' '. $sAfter;
					}
				}
			}
			else
			{
				// The target format is text/html, keep the formatting, but just change the URLs
				$aSearches = array();
				$aReplacements = array();
				foreach($aAddedAttachments as $sCID => $oAttachment)
				{
					$aSearches[] = 'src="cid:'.$sCID.'"';
					if (class_exists('InlineImage') && ($oAttachment instanceof InlineImage))
					{
						// Inline images have a special download URL requiring the 'secret' token
						$aReplacements[] = 'src="'.utils::GetAbsoluteUrlAppRoot().INLINEIMAGE_DOWNLOAD_URL.$oAttachment->GetKey().'&s='.$oAttachment->Get('secret').'"';
					}
					else
					{
						$aReplacements[] = 'src="'.utils::GetAbsoluteUrlAppRoot().ATTACHMENT_DOWNLOAD_URL.$oAttachment->GetKey().'"';
					}
				}
				$sWholeText = str_replace($aSearches, $aReplacements, $sBodyText);
			}
			$sBodyText = $sWholeText;
		}
		else
		{
			$this->Trace("Inline Images: no inline-image found in the message");
		}
		return $sBodyText;
	}
	
	/**
	 * Check (based on a set of patterns tested against the subject of the email) if the email is considered as "undesired"
	 * @param EmailMessage $oEmail The message to check
	 * @return boolean
	 */
	protected function IsUndesired(EmailMessage $oEmail)
	{
		$bRet = false; 
		if (self::$aUndesiredSubjectPatterns == null)
		{
			self::$aUndesiredSubjectPatterns = MetaModel::GetModuleSetting('combodo-email-synchro', 'undesired-subject-patterns', array());
		}
		foreach(self::$aUndesiredSubjectPatterns as $sPattern)
		{
			if (preg_match($sPattern, $oEmail->sSubject))
			{
				$this->Trace("The message '{$oEmail->sSubject}' IS considered as undesired, since it matches '$sPattern'.");
				return true;
			}
		}
		$this->Trace("The message '{$oEmail->sSubject}' is NOT considered as undesired.");
		return false; // No match, the message is NOT undesired
	}
	
	/**
	 * Error handler... what to do in case of error ??
	 * @param EmailMessage $oEmail can be null in case of decoding error (like message too big)
	 * @param string $sErrorCode
	 * @return void
	 */
	public function HandleError($oEmail, $sErrorCode, $oRawEmail = null, $sAdditionalErrorMessage = '')
	{
		$sTo = $this->Get('notify_errors_to');
		$sFrom = $this->Get('notify_errors_from');
		if ($this->Get('error_behavior') == 'delete')
		{
			$this->SetNextAction(EmailProcessor::DELETE_MESSAGE); // Remove the message from the mailbox
			$sLastAction = "<p>The eMail was deleted from the mailbox.</p>\n";
		}
		else
		{
			$this->SetNextAction(EmailProcessor::MARK_MESSAGE_AS_ERROR); // Keep the message in the mailbox, but marked as error
			$sLastAction = "<p>The eMail is marked as error and will be ignored in further processing of the mailbox.</p>\n";
		}
		
		switch($sErrorCode)
		{
			case 'unknown_contact':
			// Reject the message because of an unknown caller
			$sSubject = '[iTop] Unknown contact in incoming eMail - '.$oEmail->sSubject;
			$sBody = "<p>The following email (see attachment) comes from an unknown caller (".$oEmail->sCallerEmail.").<br/>\n";
			$sBody .= "<p>Check the configuration of the Mail Inbox '".$this->GetName()."', since the current configuration does not allow to create new contacts for unknown callers.</p>\n";
			$sBody .= $sLastAction;
			$this->sLastError = "Unknown caller (".$oEmail->sCallerEmail.")";
			break;
			
			case 'decode_failed':
			$sSubject = '[iTop] Failed to decode an incoming eMail';
			if ($oRawEmail && ($oRawEmail->GetSize() > EmailBackgroundProcess::$iMaxEmailSize))
			{
				$sBody = "<p>The incoming eMail is bigger (".$oRawEmail->GetSize()." bytes) than the maximum configured size (maximum_email_size = ".EmailBackgroundProcess::$iMaxEmailSize.").</p>\n";
				$this->sLastError = "eMail is bigger (".$oRawEmail->GetSize()." bytes) than the maximum configured size (maximum_email_size = ".EmailBackgroundProcess::$iMaxEmailSize.")";
				
				if ($this->Get('error_behavior') == 'delete')
				{
					if ($this->sBigFilesDir == '')
					{
						$sBody .= "<p>The email was deleted. In the future you can:\n<ul>\n";
						$sBody .= "<li>either increase the 'maximum_email_size' parameter in the iTop configuration file, so that the message gets processed</li>\n";
						$sBody .= "<li>or configure the parameter 'big_files_dir' in the iTop configuration file, so that such emails are kept on the web server for further inspection.</li>\n</ul>";
					}
					else if (!is_writable($this->sBigFilesDir))
					{
						$sBody .= "<p>The email was deleted, since the directory where to save such files on the web server ($this->sBigFilesDir) is NOT writable to iTop.</p>\n";
					}
					else
					{
						$idx = 1;
						$sFileName = 'email_'.(date('Y-m-d')).'_';
						$sExtension = '.eml';
						$hFile = false;
						while(($hFile = fopen($this->sBigFilesDir.'/'.$sFileName.$idx.$sExtension, 'x')) === false)
						{
							$idx++;
						}
						fwrite($hFile, $oRawEmail->GetRawContent());
						fclose($hFile);
						$sBody .= "<p>The message was saved as '{$sFileName}{$idx}{$sExtension}' on the web server, in the directory '{$this->sBigFilesDir}'.</p>\n";
						$sBody .= "<p>In order process such messages, increase the value of the 'maximum_email_size' parameter in the iTop configuration file.</p>\n";
					}
				}
				else
				{
					$sBody .= $sLastAction;
				}
								
				$oRawEmail = null; // Do not attach the original message to the mail sent to the admin since it's already big, send the message now
				$this->Trace($sSubject."\n\n".$sBody);
				// Send the email now...
				if(($sTo != '') && ($sFrom != ''))
				{
					$oEmailToSend = new Email();
			  		$oEmailToSend->SetRecipientTO($sTo);
			  		$oEmailToSend->SetSubject($sSubject);
			  		$oEmailToSend->SetBody($sBody, 'text/html');	
			  		$oEmailToSend->SetRecipientFrom($sFrom);
			  		$oEmailToSend->Send($aIssues, true /* bForceSynchronous */, null /* $oLog */);
				}
			}
			else
			{
				$sBody = "<p>The following eMail (see attachment) was not decoded properly and therefore was not processed at all.</p>\n";
				$sBody .= $sLastAction;
			}
			break;
			
			case 'nothing_to_update':
			$sSubject = '[iTop] Unable to update a ticket from the eMail - '.$oEmail->sSubject;
			$sBody = "<p>The following email (see attachment) does not seem to correspond to a ticket in iTop.<br/>\n";
			$sBody .= "The Mail Inbox ".$this->GetName()." is configured to only update existing tickets, therefore the eMail has been rejected.</p>\n";
			$sBody .= $sLastAction;
			$this->sLastError = "No corresponding iTop ticket to update (mode=update only)";
			break;
			
			case 'failed_to_create_contact':
			$sSubject = '[iTop] Failed to create a contact for the incoming eMail - '.$oEmail->sSubject;
			$sBody = "<p>The following email (see attachment) comes from an unknown caller (".$oEmail->sCallerEmail.").<br/>\n";
			$sBody .= "The configuration of the Mail Inbox ".$this->GetName()." instructs to create a new contact based on some default values, but this creation was not successful.<br/>\n";
			$sBody .= "Check the contact's default values configured in the Mail Inbox.</p>\n";
			$sBody .= $sLastAction;
			$this->sLastError = "Failed to create a contact from the incoming eMail. Caller = ".$oEmail->sCallerEmail;
			break;
			
			case 'rejected_attachments':
			$sSubject = '[iTop] Failed to process attachment(s) for the incoming eMail - '.$oEmail->sSubject;
			$sBody = "<p>Some attachments to the eMail were not processed because they are too big:</p>\n";
			$sBody .= "<pre>".$sAdditionalErrorMessage."</pre>\n";
			$sBody .= $sLastAction;
			
			$oRawEmail = null; // No original message in attachment
			$this->Trace($sSubject."\n\n".$sBody);
			// Send the email now...
			if(($sTo != '') && ($sFrom != ''))
			{
				$oEmailToSend = new Email();
		  		$oEmailToSend->SetRecipientTO($sTo);
		  		$oEmailToSend->SetSubject($sSubject);
		  		$oEmailToSend->SetBody($sBody, 'text/html');	
		  		$oEmailToSend->SetRecipientFrom($sFrom);
		  		$oEmailToSend->Send($aIssues, true /* bForceSynchronous */, null /* $oLog */);
			}
			break;
				
			case 'undesired_message':
			$sSubject = '[iTop] Undesired message - '.$oEmail->sSubject;
			$sBody = "<p>The attached message was rejected because it is considered as undesired, based on the 'undesired_subject_patterns' specified in the iTop configuration file.</p>\n";
			$sBody .= $sLastAction;
			
			// Send the email now...
			if(($sTo != '') && ($sFrom != ''))
			{
				$oEmailToSend = new Email();
		  		$oEmailToSend->SetRecipientTO($sTo);
		  		$oEmailToSend->SetSubject($sSubject);
		  		$oEmailToSend->SetBody($sBody, 'text/html');	
		  		$oEmailToSend->SetRecipientFrom($sFrom);
		  		$oEmailToSend->Send($aIssues, true /* bForceSynchronous */, null /* $oLog */);
			}
			break;
			
			default:
			$sSubject = '[iTop] handle error';
			$sBody = '<p>Unexpected error: '.$sErrorCode."</p>\n";
			$sBody .= $sLastAction;
			$this->sLastError = 'Unexpected error: '.$sErrorCode;
		}
		$sBody .= "<p>&nbsp;</p><p>Mail Inbox Configuration: ".$this->GetHyperlink()."</p>\n";
		
		if(($sTo == '') || ($sFrom == ''))
		{
			$this->Trace("HandleError($sErrorCode): No forward configured for forwarding the email...(To: '$sTo', From: '$sFrom'), skipping.");
		}
		else if($oRawEmail)
		{
			$this->Trace($sSubject."\n\n".$sBody);
			$oRawEmail->SendAsAttachment($sTo, $sFrom, $sSubject, $sBody);
		}
	}
	
	/**
	 * Make sure that the given string is a proper PCRE pattern by surrounding
	 * it with slashes, if needed
	 * @param string $sPattern The pattern to check (can be an empty string)
	 * @return string The valid pattern (or an empty string)
	 */
	protected function FixPattern($sPattern)
	{
		$sReturn = $sPattern;
		if ($sPattern != '')
		{
			$sFirstChar = substr($sPattern, 0, 1);
			$sLastChar = substr($sPattern, -1, 1);
			if (($sFirstChar != $sLastChar) || preg_match('/[0-9A-Z-a-z]/', $sFirstChar) || preg_match('/[0-9A-Z-a-z]/', $sLastChar))
			{
				// Missing delimiter patterns
				$sReturn = '/'.$sPattern.'/';
			}
		}
		return $sReturn;
	}
}

