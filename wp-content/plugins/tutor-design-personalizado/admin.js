
jQuery(document).ready(function($) {
    // Inicializar color pickers
    $(".ptl-color-picker").wpColorPicker({
        change: function() {
            updatePreview();
        }
    });
    
    // Mudar preset ao clicar
    $(".ptl-preset-card").on("click", function() {
        var preset = $(this).data("preset");
        $("#ptl_color_preset").val(preset);
        
        $(".ptl-preset-card").removeClass("active");
        $(this).addClass("active");
        
        // Atualizar cores personalizadas com base no preset
        updateColorFieldsFromPreset(preset);
        updatePreview();
    });
    
    // Toggle de cores personalizadas
    $("#ptl_use_custom_colors").on("change", function() {
        if ($(this).is(":checked")) {
            $(".ptl-custom-colors").css("opacity", "1");
        } else {
            $(".ptl-custom-colors").css("opacity", "0.5");
        }
        updatePreview();
    });
    
    // Função para atualizar campos de cores com base no preset
    function updateColorFieldsFromPreset(preset) {
        var presets = {
            "dark_purple": {
                "primary_color": "#7F00FF",
                "hover_color": "#FFA804",
                "background_color": "#121212",
                "card_background": "#424242",
                "card_active_background": "#6A6A6A",
                "text_color": "#FFFFFF",
                "complete_button_text_color": "#FFFFFF",
                "complete_button_hover_text_color": "#000000"
            },
            "blue_night": {
                "primary_color": "#0066CC",
                "hover_color": "#FF5500",
                "background_color": "#0A1929",
                "card_background": "#1E2A38",
                "card_active_background": "#2C3E50",
                "text_color": "#FFFFFF",
                "complete_button_text_color": "#FFFFFF",
                "complete_button_hover_text_color": "#000000"
            },
            "forest_green": {
                "primary_color": "#228B22",
                "hover_color": "#FFD700",
                "background_color": "#0F2012",
                "card_background": "#29472D",
                "card_active_background": "#3A6A41",
                "text_color": "#FFFFFF",
                "complete_button_text_color": "#FFFFFF",
                "complete_button_hover_text_color": "#000000"
            },
            "light_mode": {
                "primary_color": "#7F00FF",
                "hover_color": "#FFA804",
                "background_color": "#F5F5F5",
                "card_background": "#FFFFFF",
                "card_active_background": "#EEEEEE",
                "text_color": "#333333",
                "complete_button_text_color": "#FFFFFF",
                "complete_button_hover_text_color": "#000000"
            }
        };
        
        var colors = presets[preset];
        
        // Atualizar cada campo de cor
        for (var colorId in colors) {
            $("#ptl_" + colorId).val(colors[colorId]).wpColorPicker("color", colors[colorId]);
        }
    }
    
    // Atualizar preview
    function updatePreview() {
        var useCustom = $("#ptl_use_custom_colors").is(":checked");
        var colors;
        
        if (useCustom) {
            colors = {
                primary_color: $("#ptl_primary_color").val(),
                hover_color: $("#ptl_hover_color").val(),
                background_color: $("#ptl_background_color").val(),
                card_background: $("#ptl_card_background").val(),
                card_active_background: $("#ptl_card_active_background").val(),
                text_color: $("#ptl_text_color").val(),
                complete_button_text_color: $("#ptl_complete_button_text_color").val(),
                complete_button_hover_text_color: $("#ptl_complete_button_hover_text_color").val()
            };
        } else {
            var preset = $("#ptl_color_preset").val();
            colors = getPresetsColors(preset);
        }
        
        // Atualizar o preview
        $(".preview-background").css("background-color", colors.background_color);
        $(".preview-card").css("background-color", colors.card_background);
        $(".preview-header").css("background-color", colors.card_active_background);
        $(".preview-topic.active").css("background-color", colors.card_active_background);
        $(".preview-text").css("color", colors.text_color);
        $(".preview-icon").css("color", colors.text_color);
        
        // Atualizar botão de completar
        $(".preview-complete-button").css({
            "background-color": colors.primary_color,
            "color": colors.complete_button_text_color,
            "border-color": colors.primary_color
        });
        
        $(".preview-complete-icon").css("color", colors.complete_button_text_color);
        
        // Efeito hover para o botão de completar
        $(".preview-complete-button").hover(
            function() {
                $(this).css({
                    "background-color": colors.hover_color,
                    "color": colors.complete_button_hover_text_color,
                    "border-color": colors.hover_color
                });
                $(".preview-complete-icon").css("color", colors.complete_button_hover_text_color);
            }, 
            function() {
                $(this).css({
                    "background-color": colors.primary_color,
                    "color": colors.complete_button_text_color,
                    "border-color": colors.primary_color
                });
                $(".preview-complete-icon").css("color", colors.complete_button_text_color);
            }
        );
    }
    
    function getPresetsColors(preset) {
        var presets = {
            "dark_purple": {
                "primary_color": "#7F00FF",
                "hover_color": "#FFA804",
                "background_color": "#121212",
                "card_background": "#424242",
                "card_active_background": "#6A6A6A",
                "text_color": "#FFFFFF",
                "complete_button_text_color": "#FFFFFF",
                "complete_button_hover_text_color": "#000000"
            },
            "blue_night": {
                "primary_color": "#0066CC",
                "hover_color": "#FF5500",
                "background_color": "#0A1929",
                "card_background": "#1E2A38",
                "card_active_background": "#2C3E50",
                "text_color": "#FFFFFF",
                "complete_button_text_color": "#FFFFFF",
                "complete_button_hover_text_color": "#000000"
            },
            "forest_green": {
                "primary_color": "#228B22",
                "hover_color": "#FFD700",
                "background_color": "#0F2012",
                "card_background": "#29472D",
                "card_active_background": "#3A6A41",
                "text_color": "#FFFFFF",
                "complete_button_text_color": "#FFFFFF",
                "complete_button_hover_text_color": "#000000"
            },
            "light_mode": {
                "primary_color": "#7F00FF",
                "hover_color": "#FFA804",
                "background_color": "#F5F5F5",
                "card_background": "#FFFFFF",
                "card_active_background": "#EEEEEE",
                "text_color": "#333333",
                "complete_button_text_color": "#FFFFFF",
                "complete_button_hover_text_color": "#000000"
            }
        };
        
        return presets[preset];
    }
    
    // Inicializar preview
    updatePreview();
});
        