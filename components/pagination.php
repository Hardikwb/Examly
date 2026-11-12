<?php if (($totalPages ?? 1) > 1): ?>
    <nav class="pagination" aria-label="Pagination">
        <?php for ($pageNumber=1;$pageNumber<=$totalPages;$pageNumber++):$params=$paginationParams??[];$params['page']=$pageNumber;$url=($paginationPath??'').'?'.http_build_query($params); ?>
            <?php if ($pageNumber===$page): ?><span class="current" aria-current="page"><?= $pageNumber ?></span><?php else: ?><a href="<?= e(appUrl($url)) ?>"><?= $pageNumber ?></a><?php endif; ?>
        <?php endfor; ?>
    </nav>
<?php endif; ?>
