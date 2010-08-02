<?php echo Pie_Html::div('tool', 'users_contact_tool necessary panel'); ?> 
	<?php echo Pie_Html::form($tool_action_url, 'post', array('class' => 'askEmail')) ?> 
		<?php echo Pie_Html::formInfo($on_success, null, $snf) ?> 
		<h3 class='prompt'>
			<?php echo $prompt ?>
		</h3>
		<label for="authorized_email">your email address:</label>
		<input id="authorized_email" name="email_address" type="text" />
		<button type="submit" name="do_add" class="submit"><?php echo $button_content ?></button>
	</form>
</div>
