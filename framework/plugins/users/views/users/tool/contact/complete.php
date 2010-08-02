<?php if ($verified) : ?>
	<?php echo Pie_Html::div('tool', 'users_contact_tool fleeting panel'); ?>
		<h3>
			Congratulations, you've verified your email, 
			<span class='email'><?php echo $user->email_address ?></span>
		</h3>
	</div>
<?php endif; ?>
