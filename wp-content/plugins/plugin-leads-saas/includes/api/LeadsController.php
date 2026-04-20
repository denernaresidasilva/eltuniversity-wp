<?php
namespace LeadsSaaS\Api;

use LeadsSaaS\Models\Lead;
use LeadsSaaS\Services\LeadService;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class LeadsController {

    public static function register_routes(): void {
        register_rest_route( Routes::NAMESPACE, '/leads', [
            [
                'methods'             => 'GET',
                'callback'            => [ self::class, 'index' ],
                'permission_callback' => [ Routes::class, 'auth_callback' ],
            ],
            [
                'methods'             => 'POST',
                'callback'            => [ self::class, 'create' ],
                'permission_callback' => [ Routes::class, 'auth_callback' ],
            ],
        ] );

        register_rest_route( Routes::NAMESPACE, '/leads/(?P<id>\d+)', [
            [
                'methods'             => 'GET',
                'callback'            => [ self::class, 'show' ],
                'permission_callback' => [ Routes::class, 'auth_callback' ],
                'args'                => [ 'id' => [ 'sanitize_callback' => 'absint' ] ],
            ],
            [
                'methods'             => 'PUT',
                'callback'            => [ self::class, 'update' ],
                'permission_callback' => [ Routes::class, 'auth_callback' ],
                'args'                => [ 'id' => [ 'sanitize_callback' => 'absint' ] ],
            ],
            [
                'methods'             => 'DELETE',
                'callback'            => [ self::class, 'delete' ],
                'permission_callback' => [ Routes::class, 'auth_callback' ],
                'args'                => [ 'id' => [ 'sanitize_callback' => 'absint' ] ],
            ],
        ] );

        // Tag management
        register_rest_route( Routes::NAMESPACE, '/leads/(?P<id>\d+)/tags', [
            [
                'methods'             => 'POST',
                'callback'            => [ self::class, 'add_tag' ],
                'permission_callback' => [ Routes::class, 'auth_callback' ],
                'args'                => [ 'id' => [ 'sanitize_callback' => 'absint' ] ],
            ],
            [
                'methods'             => 'DELETE',
                'callback'            => [ self::class, 'remove_tag' ],
                'permission_callback' => [ Routes::class, 'auth_callback' ],
                'args'                => [ 'id' => [ 'sanitize_callback' => 'absint' ] ],
            ],
        ] );

        // CSV / spreadsheet import
        register_rest_route( Routes::NAMESPACE, '/leads/import', [
            'methods'             => 'POST',
            'callback'            => [ self::class, 'import_csv' ],
            'permission_callback' => [ Routes::class, 'auth_callback' ],
        ] );
    }

    public static function index( WP_REST_Request $request ): WP_REST_Response {
        $args = [
            'page'     => (int) $request->get_param( 'page' ) ?: 1,
            'per_page' => (int) $request->get_param( 'per_page' ) ?: 20,
            'lista_id' => (int) $request->get_param( 'lista_id' ),
            'search'   => $request->get_param( 'search' ) ?? '',
            'orderby'  => $request->get_param( 'orderby' ) ?? 'created_at',
            'order'    => $request->get_param( 'order' ) ?? 'DESC',
        ];
        $leads = Lead::all( $args );
        foreach ( $leads as &$lead ) {
            $lead['tags']        = Lead::get_tags( (int) $lead['id'] );
            $lead['campos_json'] = $lead['campos_json'] ? json_decode( $lead['campos_json'], true ) : [];
        }
        return new WP_REST_Response( [
            'items' => $leads,
            'total' => Lead::count( $args['lista_id'] ),
        ] );
    }

    public static function show( WP_REST_Request $request ): WP_REST_Response {
        $lead = Lead::find( (int) $request['id'] );
        if ( ! $lead ) {
            return new WP_REST_Response( [ 'message' => 'Lead não encontrado.' ], 404 );
        }
        return new WP_REST_Response( $lead );
    }

    public static function create( WP_REST_Request $request ): WP_REST_Response {
        $data = $request->get_json_params();
        if ( empty( $data['email'] ) ) {
            return new WP_REST_Response( [ 'message' => 'O campo e-mail é obrigatório.' ], 422 );
        }
        $id = LeadService::create_from_data( $data );
        if ( ! $id ) {
            return new WP_REST_Response( [ 'message' => 'Erro ao criar lead.' ], 500 );
        }
        return new WP_REST_Response( Lead::find( $id ), 201 );
    }

    public static function update( WP_REST_Request $request ): WP_REST_Response {
        $lead = Lead::find( (int) $request['id'] );
        if ( ! $lead ) {
            return new WP_REST_Response( [ 'message' => 'Lead não encontrado.' ], 404 );
        }
        $data = $request->get_json_params();
        Lead::update( (int) $request['id'], $data );
        return new WP_REST_Response( Lead::find( (int) $request['id'] ) );
    }

    public static function delete( WP_REST_Request $request ): WP_REST_Response {
        $lead = Lead::find( (int) $request['id'] );
        if ( ! $lead ) {
            return new WP_REST_Response( [ 'message' => 'Lead não encontrado.' ], 404 );
        }
        Lead::delete( (int) $request['id'] );
        return new WP_REST_Response( [ 'message' => 'Lead excluído com sucesso.' ] );
    }

    public static function add_tag( WP_REST_Request $request ): WP_REST_Response {
        $lead = Lead::find( (int) $request['id'] );
        if ( ! $lead ) {
            return new WP_REST_Response( [ 'message' => 'Lead não encontrado.' ], 404 );
        }
        $data   = $request->get_json_params();
        $tag_id = (int) ( $data['tag_id'] ?? 0 );
        if ( ! $tag_id ) {
            return new WP_REST_Response( [ 'message' => 'tag_id é obrigatório.' ], 422 );
        }
        LeadService::attach_tag( (int) $request['id'], $tag_id );
        return new WP_REST_Response( [ 'message' => 'Tag adicionada.' ] );
    }

    public static function remove_tag( WP_REST_Request $request ): WP_REST_Response {
        $data   = $request->get_json_params();
        $tag_id = (int) ( $data['tag_id'] ?? 0 );
        Lead::remove_tag( (int) $request['id'], $tag_id );
        return new WP_REST_Response( [ 'message' => 'Tag removida.' ] );
    }

    /**
     * POST /leads/import
     *
     * Accepts a multipart upload with:
     *   - file     : CSV or XLSX file
     *   - lista_id : target list ID
     *
     * CSV format (header row required):
     *   nome,email,telefone
     *
     * The header row is matched case-insensitively. Any column order is accepted.
     * "email" is the only required column.
     *
     * For XLSX files the first sheet is read using PHP's ZipArchive +
     * SimpleXML (no external library needed).
     */
    public static function import_csv( WP_REST_Request $request ): WP_REST_Response {
        $lista_id = (int) $request->get_param( 'lista_id' );
        if ( $lista_id <= 0 ) {
            return new WP_REST_Response( [ 'message' => 'lista_id é obrigatório.' ], 422 );
        }

        $files = $request->get_file_params();
        if ( empty( $files['file'] ) || empty( $files['file']['tmp_name'] ) ) {
            return new WP_REST_Response( [ 'message' => 'Nenhum arquivo enviado.' ], 422 );
        }

        $file     = $files['file'];
        $tmp_path = $file['tmp_name'];
        $ext      = strtolower( pathinfo( $file['name'] ?? '', PATHINFO_EXTENSION ) );

        if ( ! in_array( $ext, [ 'csv', 'xlsx', 'xls' ], true ) ) {
            return new WP_REST_Response( [ 'message' => 'Formato de arquivo não suportado. Use CSV ou XLSX.' ], 422 );
        }

        // Parse rows from the file.
        $rows = [];
        if ( 'xlsx' === $ext ) {
            $rows = self::parse_xlsx( $tmp_path );
        } else {
            // CSV / XLS-saved-as-CSV
            $rows = self::parse_csv( $tmp_path );
        }

        if ( empty( $rows ) ) {
            return new WP_REST_Response( [ 'message' => 'Arquivo vazio ou sem dados válidos.' ], 422 );
        }

        // First row is header.
        $header = array_map( 'strtolower', array_map( 'trim', $rows[0] ) );
        $col    = [
            'email'    => array_search( 'email',    $header, true ),
            'nome'     => array_search( 'nome',     $header, true ),
            'telefone' => array_search( 'telefone', $header, true ),
        ];
        // Fallback aliases for common variations.
        if ( false === $col['nome'] ) {
            foreach ( [ 'name', 'full_name', 'fullname', 'nome completo' ] as $alias ) {
                $idx = array_search( $alias, $header, true );
                if ( false !== $idx ) { $col['nome'] = $idx; break; }
            }
        }
        if ( false === $col['telefone'] ) {
            foreach ( [ 'phone', 'celular', 'cellphone', 'fone', 'mobile' ] as $alias ) {
                $idx = array_search( $alias, $header, true );
                if ( false !== $idx ) { $col['telefone'] = $idx; break; }
            }
        }

        if ( false === $col['email'] ) {
            return new WP_REST_Response( [ 'message' => 'O arquivo não contém uma coluna "email".' ], 422 );
        }

        $imported      = 0;
        $skipped       = 0;
        $error_details = [];

        foreach ( array_slice( $rows, 1 ) as $row_num => $row ) {
            $email = isset( $row[ $col['email'] ] ) ? sanitize_email( trim( $row[ $col['email'] ] ) ) : '';
            if ( empty( $email ) ) {
                $skipped++;
                $error_details[] = 'Linha ' . ( $row_num + 2 ) . ': e-mail inválido ou vazio.';
                continue;
            }

            $nome     = false !== $col['nome']     ? sanitize_text_field( trim( $row[ $col['nome'] ] ?? '' ) )     : '';
            $telefone = false !== $col['telefone'] ? sanitize_text_field( trim( $row[ $col['telefone'] ] ?? '' ) ) : '';

            $id = LeadService::create_from_data( [
                'lista_id' => $lista_id,
                'nome'     => $nome,
                'email'    => $email,
                'telefone' => $telefone,
                'origem'   => 'import',
            ], 'import' );

            if ( $id > 0 ) {
                $imported++;
            } else {
                $skipped++;
                $error_details[] = 'Linha ' . ( $row_num + 2 ) . ': falha ao salvar o e-mail "' . $email . '".';
            }
        }

        return new WP_REST_Response( [
            'imported'      => $imported,
            'skipped'       => $skipped,
            'error_details' => $error_details,
        ], 200 );
    }

    /* ------------------------------------------------------------------
       Private CSV / XLSX parsers
    ------------------------------------------------------------------ */

    /**
     * Parse a CSV file into an array of rows (each row is an array of cells).
     * Tries common delimiters (comma, semicolon, tab) automatically.
     */
    private static function parse_csv( string $path ): array {
        $handle = fopen( $path, 'r' );
        if ( ! $handle ) {
            return [];
        }

        // Detect and skip UTF-8 BOM (EF BB BF) if present.
        $bom = fread( $handle, 3 );
        if ( strlen( $bom ) < 3 || $bom !== "\xEF\xBB\xBF" ) {
            // No BOM (or file shorter than 3 bytes) – rewind to the start.
            rewind( $handle );
        }
        // If BOM was found the pointer is already past it; do NOT rewind.

        // Auto-detect delimiter by sniffing the first line, then rewind to
        // the same position so fgetcsv() re-reads the header row.
        $after_bom_pos = ftell( $handle );
        $first_line    = fgets( $handle );
        $delimiters    = [ ',', ';', "\t", '|' ];
        $best_delim    = ',';
        $best_count    = 0;
        foreach ( $delimiters as $d ) {
            $count = substr_count( $first_line === false ? '' : $first_line, $d );
            if ( $count > $best_count ) {
                $best_count = $count;
                $best_delim = $d;
            }
        }
        // Rewind to the position after any BOM so we include the header row.
        fseek( $handle, $after_bom_pos );

        $rows = [];
        while ( ( $row = fgetcsv( $handle, 0, $best_delim ) ) !== false ) {
            if ( ! empty( array_filter( $row ) ) ) {
                $rows[] = $row;
            }
        }
        fclose( $handle );
        return $rows;
    }

    /**
     * Parse the first sheet of an XLSX file into an array of rows.
     * Uses only PHP built-ins (ZipArchive + SimpleXML) – no extra library needed.
     */
    private static function parse_xlsx( string $path ): array {
        if ( ! class_exists( 'ZipArchive' ) ) {
            return [];
        }

        $zip = new \ZipArchive();
        if ( true !== $zip->open( $path ) ) {
            return [];
        }

        // Read shared strings table.
        $shared_strings = [];
        $ss_xml = $zip->getFromName( 'xl/sharedStrings.xml' );
        if ( $ss_xml ) {
            $ss = simplexml_load_string( $ss_xml );
            if ( $ss ) {
                foreach ( $ss->si as $si ) {
                    // Concatenate all <t> elements inside <si> to support rich text.
                    $text = '';
                    foreach ( $si->xpath( './/t' ) as $t ) {
                        $text .= (string) $t;
                    }
                    $shared_strings[] = $text;
                }
            }
        }

        // Find the first sheet path.
        $rels_xml = $zip->getFromName( 'xl/_rels/workbook.xml.rels' );
        $sheet_path = 'xl/worksheets/sheet1.xml'; // default fallback
        if ( $rels_xml ) {
            $rels = simplexml_load_string( $rels_xml );
            if ( $rels ) {
                foreach ( $rels->Relationship as $rel ) {
                    $type = (string) $rel['Type'];
                    if ( strpos( $type, 'worksheet' ) !== false ) {
                        $target = (string) $rel['Target'];
                        $sheet_path = strpos( $target, 'xl/' ) === 0 ? $target : 'xl/' . $target;
                        break;
                    }
                }
            }
        }

        $sheet_xml = $zip->getFromName( $sheet_path );
        $zip->close();

        if ( ! $sheet_xml ) {
            return [];
        }

        $sheet = simplexml_load_string( $sheet_xml );
        if ( ! $sheet ) {
            return [];
        }

        // Register namespaces to use xpath.
        $ns = $sheet->getNamespaces( true );
        $default_ns = $ns[''] ?? '';
        if ( $default_ns ) {
            $sheet->registerXPathNamespace( 'x', $default_ns );
        }

        $rows = [];
        $sheet_data = $sheet->sheetData ?? $sheet->children( $default_ns )->sheetData;
        if ( ! $sheet_data ) {
            return [];
        }

        foreach ( $sheet_data->children( $default_ns )->row ?? $sheet_data->row as $row ) {
            $cells     = [];
            $row_index = (int) ( $row['r'] ?? 0 );

            // Figure out the max column we'll encounter.
            $row_cells = $row->children( $default_ns )->c ?? $row->c;
            $col_map   = [];
            foreach ( $row_cells as $c ) {
                $ref   = (string) ( $c['r'] ?? '' );
                $col_l = preg_replace( '/[^A-Z]/', '', strtoupper( $ref ) );
                $col_n = self::xlsx_col_to_index( $col_l );
                $type  = (string) ( $c['t'] ?? '' );
                $v_el  = $c->children( $default_ns )->v ?? $c->v;
                $value = '';
                if ( $v_el !== null ) {
                    $raw = (string) $v_el;
                    $value = ( 's' === $type && isset( $shared_strings[ (int) $raw ] ) )
                        ? $shared_strings[ (int) $raw ]
                        : $raw;
                }
                $col_map[ $col_n ] = $value;
            }
            if ( ! empty( $col_map ) ) {
                $max = max( array_keys( $col_map ) );
                for ( $i = 0; $i <= $max; $i++ ) {
                    $cells[] = $col_map[ $i ] ?? '';
                }
                $rows[] = $cells;
            }
        }

        return $rows;
    }

    /**
     * Convert an Excel column letter (A, B, … Z, AA, AB, …) to a 0-based index.
     */
    private static function xlsx_col_to_index( string $col ): int {
        $index = 0;
        $col   = strtoupper( $col );
        $len   = strlen( $col );
        for ( $i = 0; $i < $len; $i++ ) {
            $index = $index * 26 + ( ord( $col[ $i ] ) - ord( 'A' ) + 1 );
        }
        return $index - 1;
    }
}
