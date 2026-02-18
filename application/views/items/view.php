<?php if (isset($todo['Item']) && is_array($todo['Item'])): ?>
<h2><?php echo $todo['Item']['item_name']?></h2>

<a class="big" href="?url=items/delete/<?php echo $todo['Item']['id']?>">
	<span class="item">
	Delete this item
	</span>
</a>
<?php else: ?>
<h2>Item not found.</h2>
<?php endif; ?>
