<?php

class InstagramPost extends Model
{
    /**
     * Posts with their media attached, newest first.
     */
    public function all(bool $visibleOnly = true, int $limit = 500): array
    {
        $posts = $this->fetchAll(
            'SELECT ip.*, p.name AS product_name, p.status AS product_status
             FROM instagram_posts ip
             LEFT JOIN products p ON p.id = ip.product_id
             ' . ($visibleOnly ? 'WHERE ip.is_visible = 1' : '') . '
             ORDER BY ip.posted_at DESC, ip.id DESC
             LIMIT ' . (int) $limit
        );

        if (!$posts) {
            return [];
        }

        $ids = array_map('intval', array_column($posts, 'id'));
        $media = $this->fetchAll(
            'SELECT post_id, media_path, media_type
             FROM instagram_post_media
             WHERE post_id IN (' . implode(',', $ids) . ')
             ORDER BY sort_order ASC, id ASC'
        );

        $mediaByPost = [];
        foreach ($media as $item) {
            $mediaByPost[(int) $item['post_id']][] = $item;
        }

        foreach ($posts as &$post) {
            $post['media'] = $mediaByPost[(int) $post['id']] ?? [];
        }
        unset($post);

        return $posts;
    }

    public function existsBySourceKey(string $sourceKey): bool
    {
        return $this->fetch('SELECT id FROM instagram_posts WHERE source_key = :key LIMIT 1', ['key' => $sourceKey]) !== null;
    }

    /**
     * @param array<int, array{path: string, type: string}> $media
     */
    public function create(string $sourceKey, ?string $caption, ?string $postedAt, array $media): int
    {
        $this->db->beginTransaction();

        try {
            $this->execute(
                'INSERT INTO instagram_posts (source_key, caption, posted_at) VALUES (:key, :caption, :posted_at)',
                ['key' => $sourceKey, 'caption' => $caption, 'posted_at' => $postedAt]
            );
            $postId = $this->lastInsertId();

            foreach (array_values($media) as $index => $item) {
                $this->execute(
                    'INSERT INTO instagram_post_media (post_id, media_path, media_type, sort_order)
                     VALUES (:post_id, :path, :type, :sort)',
                    ['post_id' => $postId, 'path' => $item['path'], 'type' => $item['type'], 'sort' => $index]
                );
            }

            $this->db->commit();

            return $postId;
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function linkProduct(int $postId, int $productId): bool
    {
        return $this->execute(
            'UPDATE instagram_posts SET product_id = :product_id WHERE id = :id',
            ['id' => $postId, 'product_id' => $productId]
        );
    }

    public function setVisible(int $postId, bool $visible): bool
    {
        return $this->execute(
            'UPDATE instagram_posts SET is_visible = :visible WHERE id = :id',
            ['id' => $postId, 'visible' => $visible ? 1 : 0]
        );
    }

    public function counts(): array
    {
        $row = $this->fetch(
            'SELECT COUNT(*) AS total, COALESCE(SUM(is_visible), 0) AS visible, COUNT(product_id) AS with_product
             FROM instagram_posts'
        );

        return array_map('intval', $row ?? ['total' => 0, 'visible' => 0, 'with_product' => 0]);
    }
}
