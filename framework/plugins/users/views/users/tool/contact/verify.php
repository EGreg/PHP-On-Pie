<?php echo Pie_Html::div('tool', 'users_contact_tool necessary panel'); ?> 
	<?php echo Pie_Html::form($tool_action_url, 'post', array('class' => 'askEmail')) ?> 
		<?php echo Pie_Html::formInfo($on_success, null, $snf) ?> 
		<h3 class='feedback'>
			We emailed your activation link to
			<span class='email'><?php echo $email->address ?></span>.
		</h3>
		<p>Has it been a while and you didn't get anything? Have it re-sent:</p>
		<label for="authorized_email">email address:</label>
		<input id="authorized_email" name="email_address" type="text" />
		<button type="submit" name="do_add" class="submit"><?php echo $button_content ?></button>
	</form>
</div>
