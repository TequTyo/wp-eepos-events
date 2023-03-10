<?php
wp_enqueue_style('template-css');
setlocale(LC_TIME, "fi_FI");
date_default_timezone_set('Europe/Helsinki');
$startDate = get_post_meta(get_the_ID(), 'event_start_date', true);
$startTime = get_post_meta(get_the_ID(), 'event_start_time', true);
$combinedDateTime = date('D d-m-y H:i:s', strtotime("$startDate $startTime"));
$location = get_post_meta(get_the_ID(), 'location', true);
$floor = get_post_meta(get_the_ID(), 'floor', true);
$room = get_post_meta(get_the_ID(), 'room', true);
$building = get_post_meta(get_the_ID(), 'building', true);
$roomFormat = '{room} ({floor}, {building})';
if (!empty($room) && !empty($floor) && !empty($building)) {
    $roomReplaced = str_replace(
        ['{room}', '{floor}', '{building}'],
        [$room, $floor, $building],
        $roomFormat
    );
} else {
    $roomReplaced = "";
}
$prev_post = get_adjacent_post(false,'',true);
$next_post = get_adjacent_post(false,'',false);
$next_post_link_url = get_permalink( $next_post->ID );
$prev_post_link_url = get_permalink( $prev_post->ID );
$prev_post_title = $prev_post->post_title;
$next_post_title = $next_post->post_title;
$post_content = apply_filters( 'the_content', get_the_content() );
?>
<?=get_header()?>
<?=get_sidebar()?>
<body>
       <div <?=post_class()?>>
           <div class="post-title">
               <?=the_title('<h3>', '</h3>')?>
           </div>
           <section class="element-b24a1f4 element-section">
               <div class="event-info">
                   <div class="event-datetime">
                       <div class="event-datetime-container">
                           <?php if (!empty($combinedDateTime)) { ?>
                               <p>Päivämäärä ja aika</p>
                               <span><?=$combinedDateTime?></span>
                           <?php } ?>
                       </div>
                   </div>
                   <div class="event-location">
                       <div class="event-location-container">
                           <?php if (!empty($location)) { ?>
                               <p>Paikka</p>
                               <span><?=esc_html($location)?></span>
                               <span><?=esc_html($roomReplaced)?></span>
                           <?php } ?>
                       </div>
                   </div>
                   <br />
                   <div class="event-desc">
                       <div class="event-desc-container">
                           <?php if (!empty($post_content)) { ?>
                               <span><?=$post_content?></span>
                           <?php } ?>
                       </div>
                   </div>
                   <div class="element-66922">
                       <div class="element-column-wrap">
                           <div class="element-widget-image">
                               <div class="element-widget">
                                   <div class="widget-image-container">
                                       <div class="element-image">
                                           <?php if ( has_post_thumbnail() ) { ?>
                                               <span class="image-span"><?=the_post_thumbnail( 'full' )?></span>
                                           <?php } ?>
                                       </div>
                                   </div>
                               </div>
                           </div>
                       </div>
                   </div>
               </div>
           </section>
           <section class="element-d9d1837 element-section">
               <div class="column-gap-default">
                   <div class="column-row">
                       <div class="prenex-wrapper">
                           <div class="prenex-container">
                               <div class="post-navigation">
                                   <div class="previous-post-container">
                                       <a class="previous-post-link" href="<?php echo $prev_post_link_url; ?>" rel="prev">
                                           <span class="post_arrow_wrapper">
                                               <i class="fa angle-left" aria-hidden="true"></i>
                                            </span>
                                            <span class="previous-post-container-span">
                                                <span class="previous-post-container--label">EDELLINEN TAPAHTUMA</span>
                                                <span class="previous-post-container--title"><?php echo $prev_post_title; ?></span>
                                            </span>
                                       </a>
                                   </div>
                                   <div class="next-post-container">
                                       <a class="next-post-link" href="<?php echo $next_post_link_url; ?>" rel="next">
                                           <span class="post_arrow_wrapper">
                                               <i class="fa angle-right" aria-hidden="true"></i>
                                           </span>
                                           <span class="next-post-container-span">
                                               <span class="next-post-container--label">SEURAAVA TAPAHTUMA</span><br />
                                               <span class="next-post-container--title"><?php echo $next_post_title; ?></span>
                                           </span>
                                       </a>
                                   </div>
                               </div>
                           </div>
                       </div>
                   </div>
               </div>
           </section>
       </div>
    </div>
</body>
<?=get_footer()?>
