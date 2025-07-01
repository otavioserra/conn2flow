bl_info = {
    "name": "VSE Random Video Gallery",
    "author": "Otávio Serra (Adaptado por IA)",
    "version": (1, 9, 5),
    "blender": (4, 4, 3),
    "location": "Sequencer > Sidebar (N) > Galeria VSE Tab",
    "description": "Adiciona vídeos aleatórios de uma pasta especificada à timeline, com transições e escala.",
    "warning": "Configure a 'Pasta de Vídeos' no painel do add-on.",
    "doc_url": "https://github.com/otavioserra/conn2flow/tree/blender-galeria",
    "category": "Sequencer",
}

import bpy
import os
import random

# --- Propriedades da Cena (para a UI do Painel) ---
class VSEGalleryProperties(bpy.types.PropertyGroup):
    videos_main_folder_path: bpy.props.StringProperty(
        name="Pasta de Vídeos",
        description="Selecione a pasta que contém seus vídeos. Pode ser relativo (ex: //videos/) ou absoluto",
        default="//videos/",
        subtype='DIR_PATH'
    )
    video_extensions: bpy.props.StringProperty(
        name="Extensões (vírgula)",
        description="Extensões de vídeo a procurar (ex: .mp4,.mov,.mkv)",
        default=".mp4,.mov,.mkv,.webm,.avi"
    )
    import_without_audio: bpy.props.BoolProperty(
        name="Importar Sem Áudio",
        description="Se marcado, os vídeos serão adicionados sem suas faixas de áudio originais",
        default=False
    )
    num_videos: bpy.props.IntProperty(
        name="Nº de Vídeos na Galeria",
        description="Quantos vídeos aleatórios serão adicionados",
        default=5, min=1
    )
    start_frame: bpy.props.IntProperty(
        name="Frame Inicial",
        description="Frame onde a primeira tira começará (ignorado se 'Anexar ao Final' estiver marcado)",
        default=0, min=0
    )
    append_to_end: bpy.props.BoolProperty(
        name="Anexar ao Final da Timeline",
        description="Se marcado, adiciona os vídeos após a última tira existente, ignorando o 'Frame Inicial'",
        default=False
    )
    video_channel_A: bpy.props.IntProperty(
        name="Vídeos (A)",
        description="Canal para o primeiro vídeo, terceiro, etc.",
        default=2, min=1
    )
    video_channel_B: bpy.props.IntProperty(
        name="Vídeos (B)",
        description="Canal para o segundo vídeo, quarto, etc.",
        default=3, min=1
    )
    transition_channel: bpy.props.IntProperty(
        name="Transições",
        description="Canal para as transições 'Cross'. Deve ser maior que os canais de vídeo/som",
        default=4, min=1
    )
    transition_duration: bpy.props.IntProperty(
        name="Duração Transição (frames)",
        description="Quantos frames a transição 'Cross' deve durar",
        default=30, min=1
    )
    scale_mode: bpy.props.EnumProperty(
        name="Modo de Escala",
        description="Como os vídeos serão ajustados para o tamanho da cena",
        items=[('FIT', "Conter (Contain)", "Mostra o vídeo inteiro"),
               ('FILL', "Cobrir (Cover)", "Preenche a tela")],
        default='FIT'
    )
    clear_target_channels: bpy.props.BoolProperty(
        name="Limpar Todos os Canais Antes",
        description="Se marcado, remove todas as tiras do Sequencer antes de adicionar as novas (desabilitado se 'Anexar' estiver ativo)",
        default=False
    )

# --- Funções do Script ---
def get_gallery_props(context):
    return context.scene.vse_gallery_tool

def encontrar_videos_recursivamente(pasta_base_videos, extensoes_validas_tupla, context):
    arquivos_de_video_encontrados = []
    if not os.path.isdir(pasta_base_videos): return arquivos_de_video_encontrados
    
    all_potential_files = []
    for dirpath, dirnames, filenames in os.walk(pasta_base_videos):
        if 'BL_proxy' in dirnames:
            dirnames.remove('BL_proxy')
            
        for filename in filenames:
            all_potential_files.append(os.path.join(dirpath, filename))

    wm = context.window_manager
    wm.progress_begin(0, len(all_potential_files))
    
    for i, caminho_completo_abs in enumerate(all_potential_files):
        if caminho_completo_abs.lower().endswith(extensoes_validas_tupla):
            arquivos_de_video_encontrados.append(caminho_completo_abs)
        wm.progress_update(i + 1)
    
    wm.progress_end()
    
    return arquivos_de_video_encontrados

# --- Operadores do Blender ---
class VSEGALLERY_OT_BuildPlaylist(bpy.types.Operator):
    bl_idname = "vsegallery.build_playlist"
    bl_label = "1. Construir/Atualizar Lista"
    bl_options = {'REGISTER', 'UNDO'}
    def execute(self, context):
        props = get_gallery_props(context)
        user_provided_path = props.videos_main_folder_path
        if not user_provided_path:
            self.report({'ERROR'}, "O campo 'Pasta de Vídeos' está vazio.")
            VSEGALLERY_PT_Panel.available_videos_list = []
            return {'CANCELLED'}
        try: absolute_videos_path = bpy.path.abspath(user_provided_path)
        except Exception as e:
            self.report({'ERROR'}, f"Não foi possível resolver o caminho: {e}. Salve seu arquivo .blend.")
            return {'CANCELLED'}
        if not os.path.isdir(absolute_videos_path):
            self.report({'ERROR'}, f"O caminho '{absolute_videos_path}' não é válido.")
            return {'CANCELLED'}
        
        extensoes_tupla = tuple(ext.strip() for ext in props.video_extensions.split(',') if ext.strip())
        
        context.window.cursor_set('WAIT')
        try:
            VSEGALLERY_PT_Panel.available_videos_list = encontrar_videos_recursivamente(absolute_videos_path, extensoes_tupla, context)
        finally:
            context.window.cursor_set('DEFAULT')

        if VSEGALLERY_PT_Panel.available_videos_list:
            self.report({'INFO'}, f"Lista construída com {len(VSEGALLERY_PT_Panel.available_videos_list)} vídeos.")
        else:
            self.report({'WARNING'}, f"Nenhum vídeo encontrado em '{absolute_videos_path}'.")
        return {'FINISHED'}


class VSEGALLERY_OT_AddGalleryToVSE(bpy.types.Operator):
    bl_idname = "vsegallery.add_to_timeline"
    bl_label = "2. Adicionar Galeria à Timeline"
    bl_options = {'REGISTER', 'UNDO'}

    def execute(self, context):
        props = get_gallery_props(context)
        
        if not VSEGALLERY_PT_Panel.available_videos_list:
            self.report({'ERROR'}, "Construa a lista de vídeos primeiro!")
            return {'CANCELLED'}

        cena = context.scene
        if not cena.sequence_editor:
            cena.sequence_editor_create()
        
        sequenciador = cena.sequence_editor
        tiras = sequenciador.sequences

        if props.clear_target_channels and not props.append_to_end:
            if tiras:
                EFFECT_TYPES = {'CROSS', 'ADD', 'SUBTRACT', 'ALPHA_OVER', 'ALPHA_UNDER', 
                                'GAMMA_CROSS', 'MULTIPLY', 'OVER_DROP', 'WIPE', 
                                'TRANSFORM', 'COLOR', 'SPEED', 'MULTICAM', 'ADJUSTMENT'}
                strips_to_remove = list(tiras)
                effects = [s for s in strips_to_remove if s.type in EFFECT_TYPES]
                bases = [s for s in strips_to_remove if s.type not in EFFECT_TYPES]
                for effect_strip in effects:
                    try: tiras.remove(effect_strip)
                    except: pass
                for base_strip in bases:
                    try: tiras.remove(base_strip)
                    except: pass
                self.report({'INFO'}, "Todos os canais foram limpos.")

        tira_anterior_video = None
        if props.append_to_end and tiras:
            base_strips = [s for s in tiras if s.type in {'MOVIE', 'IMAGE'}]
            if base_strips:
                tira_anterior_video = max(base_strips, key=lambda s: s.frame_final_end)
                frame_atual_para_proxima_tira = tira_anterior_video.frame_final_end
            else:
                frame_atual_para_proxima_tira = props.start_frame
        else:
            frame_atual_para_proxima_tira = props.start_frame

        videos_adicionados_count = 0
        
        num_a_pegar = min(props.num_videos, len(VSEGALLERY_PT_Panel.available_videos_list))
        videos_para_selecao = random.sample(VSEGALLERY_PT_Panel.available_videos_list, num_a_pegar)
        
        start_on_channel_A = True
        if tira_anterior_video:
            if tira_anterior_video.channel == props.video_channel_A:
                start_on_channel_A = False
        
        wm = context.window_manager
        wm.progress_begin(0, num_a_pegar)
        context.window.cursor_set('WAIT')

        try:
            for i, caminho_video_escolhido in enumerate(videos_para_selecao):
                if props.append_to_end and i == 0:
                    frame_de_inicio_da_tira_atual = int(frame_atual_para_proxima_tira - props.transition_duration) if tira_anterior_video and props.transition_duration > 0 else int(frame_atual_para_proxima_tira)
                elif i > 0 and tira_anterior_video and props.transition_duration > 0:
                    frame_de_inicio_da_tira_atual = int(tira_anterior_video.frame_final_end - props.transition_duration)
                else:
                    frame_de_inicio_da_tira_atual = int(frame_atual_para_proxima_tira)

                if start_on_channel_A:
                    canal_video_atual = props.video_channel_A if i % 2 == 0 else props.video_channel_B
                else:
                    canal_video_atual = props.video_channel_B if i % 2 == 0 else props.video_channel_A
                
                nova_tira_video = tiras.new_movie(
                    name=os.path.basename(caminho_video_escolhido),
                    filepath=caminho_video_escolhido,
                    channel=canal_video_atual,
                    frame_start=frame_de_inicio_da_tira_atual,
                    fit_method=props.scale_mode
                )

                if nova_tira_video:
                    nova_tira_audio = next((s for s in tiras if s.frame_start == nova_tira_video.frame_start and s.channel == nova_tira_video.channel + 1 and s.type == 'SOUND'), None)
                    if props.import_without_audio and nova_tira_audio:
                        tiras.remove(nova_tira_audio)

                    if tira_anterior_video and props.transition_duration > 0:
                        inicio_transicao = nova_tira_video.frame_start
                        fim_transicao = inicio_transicao + props.transition_duration
                        efeito_cross = tiras.new_effect(
                            name=f"Cross_{tira_anterior_video.name[:10]}_{nova_tira_video.name[:10]}",
                            type='CROSS', channel=props.transition_channel,
                            frame_start=int(inicio_transicao),
                            frame_end=int(fim_transicao), 
                            seq1=tira_anterior_video, seq2=nova_tira_video
                        )
                    
                    frame_atual_para_proxima_tira = nova_tira_video.frame_final_end
                    tira_anterior_video = nova_tira_video
                    videos_adicionados_count += 1
                
                wm.progress_update(i + 1)
        except Exception as e:
            self.report({'ERROR'}, f"Ocorreu um erro durante a adição à timeline: {e}")
        finally:
            wm.progress_end()
            context.window.cursor_set('DEFAULT')

        if videos_adicionados_count > 0:
            self.report({'INFO'}, f"{videos_adicionados_count} vídeos adicionados à timeline.")
        else:
            self.report({'WARNING'}, "Nenhum vídeo foi efetivamente adicionado.")
        
        bpy.ops.sequencer.view_all()
        return {'FINISHED'}

# --- Painel da UI e Registro ---
class VSEGALLERY_PT_Panel(bpy.types.Panel):
    bl_label = "Galeria VSE Aleatória"
    bl_idname = "SEQ_PT_random_video_gallery"
    bl_space_type = 'SEQUENCE_EDITOR'
    bl_region_type = 'UI'
    bl_category = "GaleriaVSE"
    available_videos_list = []

    def draw(self, context):
        layout = self.layout
        props = get_gallery_props(context)
        
        box = layout.box()
        box.label(text="Configurações da Galeria:")
        box.prop(props, "videos_main_folder_path")
        box.prop(props, "video_extensions")
        box.prop(props, "import_without_audio")
        box.prop(props, "num_videos")
        
        row = box.row(align=True)
        row.prop(props, "append_to_end")
        sub_row = row.row()
        sub_row.enabled = not props.append_to_end
        sub_row.prop(props, "start_frame")
        
        col = layout.column(align=True)
        col.label(text="Organização de Canais:")
        row_channels = col.row(align=True)
        row_channels.prop(props, "video_channel_A", text="Vídeos A")
        row_channels.prop(props, "video_channel_B", text="Vídeos B")
        row_channels.prop(props, "transition_channel", text="Transições")
        col.label(text="Nota: Para alternar, deixe canais A e B diferentes.")
        
        layout.prop(props, "transition_duration")
        layout.prop(props, "scale_mode")

        clear_row = layout.row()
        clear_row.enabled = not props.append_to_end
        clear_row.prop(props, "clear_target_channels")

        layout.separator()
        layout.operator(VSEGALLERY_OT_BuildPlaylist.bl_idname)
        
        if VSEGALLERY_PT_Panel.available_videos_list:
            layout.label(text=f"Vídeos encontrados: {len(VSEGALLERY_PT_Panel.available_videos_list)}")
            layout.operator(VSEGALLERY_OT_AddGalleryToVSE.bl_idname)
        else:
            layout.label(text="Nenhum vídeo na lista. Clique em 'Construir'.")

classes_to_register = (
    VSEGalleryProperties,
    VSEGALLERY_OT_BuildPlaylist,
    VSEGALLERY_OT_AddGalleryToVSE,
    VSEGALLERY_PT_Panel,
)

def register():
    for cls in classes_to_register:
        bpy.utils.register_class(cls)
    bpy.types.Scene.vse_gallery_tool = bpy.props.PointerProperty(type=VSEGalleryProperties)
    VSEGALLERY_PT_Panel.available_videos_list = []

def unregister():
    del bpy.types.Scene.vse_gallery_tool
    for cls in reversed(classes_to_register):
        bpy.utils.unregister_class(cls)

if __name__ == "__main__":
    try: unregister()
    except Exception: pass
    register()
