import java.io.IOException;
import java.util.HashMap;
import java.util.Map;
import java.util.StringTokenizer;
import org.apache.hadoop.mapred.FileSplit;
import javax.naming.Context;
import org.apache.hadoop.fs.Path;
import org.apache.hadoop.mapred.Reporter;
import org.apache.hadoop.conf.Configuration;
import org.apache.hadoop.fs.Path;
import org.apache.hadoop.io.IntWritable;
import org.apache.hadoop.io.Text;
import org.apache.hadoop.mapreduce.Job;
import org.apache.hadoop.mapreduce.Mapper;
import org.apache.hadoop.mapreduce.Reducer;
import org.apache.hadoop.mapreduce.lib.input.FileInputFormat;
import org.apache.hadoop.mapreduce.lib.output.FileOutputFormat;
import org.apache.hadoop.io.LongWritable;

public class InvertedIndexJob {
	
	public static class FullMapper
       extends Mapper<LongWritable , Text, Text, Text>{

    private Text word = new Text();
    String documentId;
    
    public void map(LongWritable key, Text value, Context context
                    ) throws IOException, InterruptedException, Exception {
    	
    	StringTokenizer itr = new StringTokenizer(value.toString(),"\t");
    	documentId = itr.nextToken(); 
    	StringTokenizer tokenizer = new StringTokenizer(itr.nextToken());
    	
        while (tokenizer.hasMoreTokens()) {
        	word.set(tokenizer.nextToken());
            context.write(word,new Text(documentId));
        	}
    	}
  	}

	public static class FullReducer
       extends Reducer<Text,Text,Text,Text> {
    
	public void reduce(Text key, Iterable<Text> values,
                       Context context
                       ) throws IOException, InterruptedException, Exception {
 
    	int sum=0;
    	
    	int x;
    	HashMap<String,Integer> map = new HashMap<String,Integer>();
    	System.out.println("Here");
    	 for(Text val:values){
    		 if(map.containsKey(val.toString())){
    			 x = map.get(val.toString());
    			 x++;
    			 map.put(val.toString(), x);
    		 }else{
    			 map.put(val.toString(), 1);
    		 }
    	 }
	System.out.println(map.keySet());
    	String result = ""; 
	for(String val : map.keySet()){
    		 result += val+":"+map.get(val)+" ";
    	 }
    	 context.write(key,new Text(result));
	}
}
  
  public static void main(String[] args) throws IOException, ClassNotFoundException, InterruptedException {
    Configuration conf = new Configuration();
    if (args.length != 2){
System.err.println("Both input /output needed");
System.exit(-1);
}
    Job job = Job.getInstance(conf);
    job.setJarByClass(InvertedIndexJob.class);
    job.setMapperClass(FullMapper.class);
    job.setReducerClass(FullReducer.class);
    job.setOutputKeyClass(Text.class);
    job.setOutputValueClass(Text.class);
    FileInputFormat.addInputPath(job, new Path(args[0]));
    FileOutputFormat.setOutputPath(job, new Path(args[1]));
    job.waitForCompletion(true);
  }
}
