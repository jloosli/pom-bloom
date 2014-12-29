<nav class="menu">
    <ul>
        <li class="<?php echo $vars['active']==='overview' ? 'active':''; ?>">
            <a onclick="bloomNav(this);" href="">Overview</a>
        </li>
        <li class="<?php echo $vars['active']==='assessments' ? 'active':''; ?>">
            <a onclick="bloomNav(this);" href="assessments">Assessments</a>
        </li>
        <li class="<?php echo $vars['active']==='preferences' ? 'active':''; ?>">
            <a onclick="bloomNav(this);" href="preferences">Preferences</a>
        </li>
    </ul>
</nav>
