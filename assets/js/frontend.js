/**
 * Blocks Test — Frontend JavaScript
 *
 * Inter-block communication: CustomEvent bus on document.
 */

( function () {
    'use strict';

    // =========================================================================
    // Posts Filter
    // =========================================================================
    function initFilter( wrapper ) {
        var chips    = wrapper.querySelectorAll( '.bt-filter-chip' );
        var clearBtn = wrapper.querySelector( '.bt-filter-clear' );
        var state    = { categories: [], tags: [] };

        function dispatch() {
            document.dispatchEvent(
                new CustomEvent( 'bt:filter-change', {
                    bubbles: true,
                    detail:  { categories: state.categories.slice(), tags: state.tags.slice(), page: 1 },
                } )
            );
        }

        function updateClearVisibility() {
            clearBtn.hidden = state.categories.length === 0 && state.tags.length === 0;
        }

        chips.forEach( function ( chip ) {
            chip.addEventListener( 'click', function () {
                var type   = chip.dataset.filterType;
                var termId = parseInt( chip.dataset.termId, 10 );
                var arr    = state[ type ];
                var idx    = arr.indexOf( termId );

                if ( idx === -1 ) {
                    arr.push( termId );
                    chip.setAttribute( 'aria-pressed', 'true' );
                    chip.classList.add( 'is-active' );
                } else {
                    arr.splice( idx, 1 );
                    chip.setAttribute( 'aria-pressed', 'false' );
                    chip.classList.remove( 'is-active' );
                }

                updateClearVisibility();
                dispatch();
            } );
        } );

        clearBtn.addEventListener( 'click', function () {
            state.categories = [];
            state.tags       = [];
            chips.forEach( function ( chip ) {
                chip.setAttribute( 'aria-pressed', 'false' );
                chip.classList.remove( 'is-active' );
            } );
            updateClearVisibility();
            dispatch();
        } );
    }

    // =========================================================================
    // Posts Grid
    // =========================================================================
    function initGrid( wrapper ) {
        var config;

        if ( wrapper.dataset.initial ) {
            try { config = JSON.parse( wrapper.dataset.initial ); } catch ( e ) { return; }
        } else {
            config = {
                columns:      parseInt( wrapper.dataset.columns, 10 )     || 3,
                postsPerPage: parseInt( wrapper.dataset.postsPerPage, 10 ) || 6,
                totalPages:   1,
                currentPage:  1,
                restUrl:      wrapper.dataset.restUrl || '',
                nonce:        wrapper.dataset.nonce   || '',
            };
            if ( ! config.restUrl ) return;
        }

        var currentPage       = config.currentPage || 1;
        var currentCategories = [];
        var currentTags       = [];
        var columns           = config.columns || 3;

        var grid    = wrapper.querySelector( '.bt-posts-grid' );
        var prevBtn = wrapper.querySelector( '.bt-pagination__btn--prev' );
        var nextBtn = wrapper.querySelector( '.bt-pagination__btn--next' );
        var current = wrapper.querySelector( '.bt-pagination__current' );
        var total   = wrapper.querySelector( '.bt-pagination__total' );
        var nav     = wrapper.querySelector( '.bt-pagination' );

        // ---- Helpers --------------------------------------------------------

        function escHtml( str ) {
            var d = document.createElement( 'div' );
            d.textContent = str;
            return d.innerHTML;
        }

        function renderCard( post ) {
            var img = post.thumbnail
                ? '<a href="' + escHtml( post.permalink ) + '" class="bt-post-card__image-link" tabindex="-1" aria-hidden="true">' +
                '<img src="' + escHtml( post.thumbnail ) + '" alt="' + escHtml( post.title ) + '" class="bt-post-card__image" loading="lazy">' +
                '</a>'
                : '';

            return '<article class="bt-post-card"' +
                ' data-id="'   + post.id + '"' +
                ' data-cats="' + ( post.categories || [] ).join( ',' ) + '"' +
                ' data-tags="' + ( post.tags       || [] ).join( ',' ) + '">' +
                img +
                '<div class="bt-post-card__body">' +
                '<time class="bt-post-card__date">' + escHtml( post.date ) + '</time>' +
                '<h3 class="bt-post-card__title"><a href="' + escHtml( post.permalink ) + '">' + escHtml( post.title ) + '</a></h3>' +
                '<p class="bt-post-card__excerpt">' + escHtml( post.excerpt ) + '</p>' +
                '</div></article>';
        }

        function setLoading( loading ) {
            wrapper.classList.toggle( 'is-loading', loading );
            if ( prevBtn ) prevBtn.disabled = loading;
            if ( nextBtn ) nextBtn.disabled = loading;
        }

        function updatePagination( data ) {
            if ( current ) current.textContent = data.currentPage;
            if ( total )   total.textContent   = data.totalPages;
            if ( prevBtn ) prevBtn.disabled     = data.currentPage <= 1;
            if ( nextBtn ) nextBtn.disabled     = data.currentPage >= data.totalPages;
            if ( nav )     nav.style.display    = data.totalPages <= 1 ? 'none' : '';
        }

        function fetchPosts( page, categories, tags ) {
            setLoading( true );

            var url = new URL( config.restUrl );
            url.searchParams.set( 'page',     page );
            url.searchParams.set( 'per_page', config.postsPerPage || 6 );

            ( categories || [] ).forEach( function ( id ) {
                url.searchParams.append( 'categories[]', id );
            } );
            ( tags || [] ).forEach( function ( id ) {
                url.searchParams.append( 'tags[]', id );
            } );

            fetch( url.toString(), { headers: { 'X-WP-Nonce': config.nonce } } )
                .then( function ( r ) { return r.json(); } )
                .then( function ( data ) {
                    grid.classList.add( 'is-transitioning' );
                    setTimeout( function () {
                        grid.innerHTML = ( data.posts || [] ).map( renderCard ).join( '' );
                        grid.className = 'bt-posts-grid bt-posts-grid--cols-' + columns;
                        void grid.offsetHeight;
                        grid.classList.remove( 'is-transitioning' );
                        updatePagination( data );
                        currentPage = data.currentPage;
                    }, 160 );
                } )
                .catch( function () {
                    grid.innerHTML = '<p class="bt-error">Could not load posts. Please try again.</p>';
                } )
                .finally( function () { setLoading( false ); } );
        }

        // ---- Init -----------------------------------------------------------

        /* Set the pagination buttons after the page is loaded */

        if ( config.posts && config.posts.length ) {
            updatePagination( {
                currentPage: config.currentPage || 1,
                totalPages:  config.totalPages  || 1,
            } );
        } else {
            fetchPosts( 1, [], [] );
        }

        if ( prevBtn ) {
            prevBtn.addEventListener( 'click', function () {
                if ( currentPage > 1 ) fetchPosts( currentPage - 1, currentCategories, currentTags );
            } );
        }

        if ( nextBtn ) {
            nextBtn.addEventListener( 'click', function () {
                fetchPosts( currentPage + 1, currentCategories, currentTags );
            } );
        }

        document.addEventListener( 'bt:filter-change', function ( e ) {
            currentCategories = e.detail.categories || [];
            currentTags       = e.detail.tags       || [];
            fetchPosts( 1, currentCategories, currentTags );
        } );
    }

    // =========================================================================
    // Boot
    // =========================================================================
    function boot() {
        document.querySelectorAll( '.bt-posts-filter' ).forEach( initFilter );
        document.querySelectorAll( '.bt-posts-grid-wrapper' ).forEach( initGrid );
    }

    if ( document.readyState === 'loading' ) {
        document.addEventListener( 'DOMContentLoaded', boot );
    } else {
        boot();
    }

} )();