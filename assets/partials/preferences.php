<?php //var_dump($vars); ?>
<h2>Bloom Preferences for <?php echo $vars['current_user']->user_nicename ?></h2>
<div class="instructions">
    Choose your level. You can switch levels at any time. When you switch levels, the next time you set goals,
    we'll have you all set up with the right number of goals.
</div>
<div>
    <form id="pref_submit">
        <label>
            <input type="radio" name="program_type" value="beginner" />
            Beginner <small>(3 goals and 2 serendipity moments)</small>
        </label><br>
        <label>
            <input type="radio" name="program_type" value="advanced" />
            Advanced <small>(5 goals and 3 serendipity moments)</small>
        </label><br>
        <input type="submit" name="submit" value="Update Preferences"/>
    </form>
</div>