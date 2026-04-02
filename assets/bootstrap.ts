// @ts-expect-error "@symfony/stimulus-bundle is JS code without a types definition"
import { startStimulusApp } from "@symfony/stimulus-bundle";
// @ts-expect-error "@enterprise-tooling-for-symfony/webui is JS code without a types definition"
import { webuiBootstrap } from "@enterprise-tooling-for-symfony/webui";
import LiveSearchController from "./controllers/live_search_controller.ts";
import FileUploadController from "../src/FileImport/Presentation/Resources/assets/controllers/file_upload_controller.ts";
import ProjectTrackReorderController from "../src/ProjectManagement/Presentation/Resources/assets/controllers/project_track_reorder_controller.ts";
import ChecklistReorderController from "../src/TrackManagement/Presentation/Resources/assets/controllers/checklist_reorder_controller.ts";
import TrackTitleController from "../src/TrackManagement/Presentation/Resources/assets/controllers/track_title_controller.ts";

const app = startStimulusApp();
// register any custom, 3rd party controllers here
// app.register('some_controller_name', SomeImportedController);
app.register("live-search", LiveSearchController);
app.register("file-upload", FileUploadController);
app.register("project-track-reorder", ProjectTrackReorderController);
app.register("checklist-reorder", ChecklistReorderController);
app.register("track-title", TrackTitleController);

webuiBootstrap(app);
