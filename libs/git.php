<?php
function getBranchName() {
$stringfromfile = file('.git/HEAD', FILE_USE_INCLUDE_PATH);
$firstLine = $stringfromfile[0]; //get the string from the array
$explodedstring = explode("/", $firstLine, 3); //seperate out by the "/" in the string
$branchname = trim($explodedstring[2]); //get the one that is always the branch name
return $branchname;
}

/**
 * Split shell_exec output into non-empty trimmed lines.
 * @param string $output
 * @return string[]
 */
function gitOutputLines($output) {
    $lines = preg_split("/\r\n|\n|\r/", (string)$output);
    $out = array();
    foreach($lines as $line) {
        $line = rtrim($line);
        if($line !== '') {
            $out[] = $line;
        }
    }
    return $out;
}

/**
 * Escape a shell argument for git commands.
 * @param string $value
 * @return string
 */
function gitShellArg($value) {
    return escapeshellarg((string)$value);
}

/**
 * Fetch from origin. Returns raw output lines.
 * @return string[]
 */
function gitFetchOrigin() {
    return gitOutputLines(shell_exec('git fetch origin 2>&1'));
}

/**
 * Short SHA for a ref (e.g. HEAD or origin/master).
 * @param string $ref
 * @return string
 */
function gitShortSha($ref) {
    $cmd = 'git rev-parse --short '.gitShellArg($ref).' 2>&1';
    return trim((string)shell_exec($cmd));
}

/**
 * Compare local HEAD to origin/{branch} after a fetch.
 * @param string $branch
 * @return array{branch:string,ahead:int,behind:int,localSha:string,remoteSha:string,logLines:string[],error:?string}
 */
function gitRemoteUpdateStatus($branch) {
    $branch = (string)$branch;
    $remoteRef = 'origin/'.$branch;
    $localSha = gitShortSha('HEAD');
    $remoteSha = gitShortSha($remoteRef);
    $ahead = 0;
    $behind = 0;
    $error = null;

    if($remoteSha === '' || strpos($remoteSha, 'fatal:') !== false || strpos($remoteSha, 'unknown revision') !== false) {
        $error = 'Remote-Branch '.$remoteRef.' nicht gefunden.';
        return array(
            'branch' => $branch,
            'ahead' => 0,
            'behind' => 0,
            'localSha' => $localSha,
            'remoteSha' => '',
            'logLines' => array(),
            'error' => $error,
        );
    }

    $countRaw = trim((string)shell_exec(
        'git rev-list --left-right --count HEAD...'.gitShellArg($remoteRef).' 2>&1'
    ));
    if(preg_match('/^(\d+)\s+(\d+)$/', $countRaw, $m)) {
        $ahead = (int)$m[1];
        $behind = (int)$m[2];
    }
    else {
        $error = $countRaw !== '' ? $countRaw : 'Vergleich fehlgeschlagen.';
    }

    $logLines = array();
    if($behind > 0) {
        $logCmd = 'git log --oneline -n 10 HEAD..'.gitShellArg($remoteRef).' 2>&1';
        $logLines = gitOutputLines(shell_exec($logCmd));
    }

    return array(
        'branch' => $branch,
        'ahead' => $ahead,
        'behind' => $behind,
        'localSha' => $localSha,
        'remoteSha' => $remoteSha,
        'logLines' => $logLines,
        'error' => $error,
    );
}

/**
 * Fetch origin and return update status for the current branch.
 * @param string|null $branch
 * @return array{fetchLines:string[],status:array}
 */
function gitCheckForUpdates($branch = null) {
    if($branch === null || $branch === '') {
        $branch = getBranchName();
    }
    $fetchLines = gitFetchOrigin();
    $status = gitRemoteUpdateStatus($branch);
    return array(
        'fetchLines' => $fetchLines,
        'status' => $status,
    );
}

/**
 * Pull origin/{branch}. Returns before/after SHA and pull output lines.
 * @param string|null $branch
 * @return array{branch:string,vCurrent:string,vNew:string,updated:bool,lines:string[]}
 */
function gitPullOrigin($branch = null) {
    if($branch === null || $branch === '') {
        $branch = getBranchName();
    }
    $vCurrent = gitShortSha('HEAD');
    $lines = gitOutputLines(shell_exec('git pull origin '.gitShellArg($branch).' 2>&1'));
    $vNew = gitShortSha('HEAD');
    return array(
        'branch' => $branch,
        'vCurrent' => $vCurrent,
        'vNew' => $vNew,
        'updated' => ($vCurrent !== $vNew),
        'lines' => $lines,
    );
}
?>
